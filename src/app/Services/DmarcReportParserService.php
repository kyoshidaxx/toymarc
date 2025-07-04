<?php

namespace App\Services;

use App\Models\DmarcReport;
use App\Models\DmarcRecord;
use Exception;
use SimpleXMLElement;

class DmarcReportParseException extends Exception
{
}

class DmarcReportParserService
{
    /**
     * Parse DMARC XML report content.
     *
     * @throws DmarcReportParseException
     */
    public function parseXmlReport(string $xmlContent): DmarcReport
    {
        try {
            $xml = new SimpleXMLElement($xmlContent);
            
            // 名前空間の確認とデバッグ
            $namespaces = $xml->getNamespaces();
            if (!empty($namespaces)) {
                $xml->registerXPathNamespace('dmarc', 'http://dmarc.org/dmarc-xml/1.0');
            }

            // Parse report metadata
            $metadata = $this->parseReportMetadata($xml);

            // Parse policy published
            $policy = $this->parsePolicyPublished($xml);

            // Parse records
            $records = $this->parseRecords($xml);

            // Create DmarcReport instance
            $report = new DmarcReport([
                'org_name' => $metadata['org_name'],
                'email' => $metadata['email'],
                'report_id' => $metadata['report_id'],
                'begin_date' => $metadata['begin_date'],
                'end_date' => $metadata['end_date'],
                'policy_domain' => $policy['domain'],
                'policy_p' => $policy['p'],
                'policy_pct' => $policy['pct'],
                'raw_data' => json_encode((array)$xml),
            ]);

            // Set records relationship
            $report->setRelation('records', collect($records));

            return $report;
        } catch (Exception $e) {
            throw new DmarcReportParseException(
                'DMARCレポートの解析に失敗しました: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Parse report metadata from XML.
     * 
     * @return array<string, mixed>
     */
    private function parseReportMetadata(SimpleXMLElement $xml): array
    {
        $metadata = $xml->xpath('/*[local-name()="feedback"]/*[local-name()="report_metadata"]')[0] ?? null;
        if (!$metadata) {
            throw new DmarcReportParseException('レポートメタデータが見つかりません');
        }

        $dateRange = $metadata->xpath('./*[local-name()="date_range"]')[0] ?? null;
        if (!$dateRange) {
            throw new DmarcReportParseException('日付範囲が見つかりません');
        }

        return [
            'org_name' => (string) ($metadata->xpath('./*[local-name()="org_name"]')[0] ?? ''),
            'email' => (string) ($metadata->xpath('./*[local-name()="email"]')[0] ?? ''),
            'report_id' => (string) ($metadata->xpath('./*[local-name()="report_id"]')[0] ?? ''),
            'begin_date' => date('Y-m-d H:i:s', (int) ($dateRange->xpath('./*[local-name()="begin"]')[0] ?? 0)),
            'end_date' => date('Y-m-d H:i:s', (int) ($dateRange->xpath('./*[local-name()="end"]')[0] ?? 0)),
        ];
    }

    /**
     * Parse policy published information from XML.
     * 
     * @return array<string, mixed>
     */
    private function parsePolicyPublished(\SimpleXMLElement $xml): array
    {
        $policy = $xml->xpath('/*[local-name()="feedback"]/*[local-name()="policy_published"]')[0] ?? null;
        if (!$policy) {
            throw new DmarcReportParseException('ポリシー情報が見つかりません');
        }

        return [
            'domain' => (string) ($policy->xpath('./*[local-name()="domain"]')[0] ?? ''),
            'p' => (string) ($policy->xpath('./*[local-name()="p"]')[0] ?? 'none'),
            'pct' => (int) ($policy->xpath('./*[local-name()="pct"]')[0] ?? 100),
        ];
    }

    /**
     * Parse records from XML.
     * 
     * @return array<int, \App\Models\DmarcRecord>
     */
    private function parseRecords(\SimpleXMLElement $xml): array
    {
        $records = [];
        $recordNodes = $xml->xpath('/*[local-name()="feedback"]/*[local-name()="record"]');

        foreach ($recordNodes as $recordNode) {
            $row = $recordNode->xpath('./*[local-name()="row"]')[0] ?? null;
            $policyEvaluated = $row ? $row->xpath('./*[local-name()="policy_evaluated"]')[0] ?? null : null;
            $identifiers = $recordNode->xpath('./*[local-name()="identifiers"]')[0] ?? null;
            $authResults = $recordNode->xpath('./*[local-name()="auth_results"]')[0] ?? null;

            if (!$row || !$policyEvaluated) {
                continue;
            }

            $records[] = new DmarcRecord([
                'source_ip' => (string) ($row->xpath('./*[local-name()="source_ip"]')[0] ?? ''),
                'count' => (int) ($row->xpath('./*[local-name()="count"]')[0] ?? 0),
                'disposition' => (string) ($policyEvaluated->xpath('./*[local-name()="disposition"]')[0] ?? ''),
                'dkim_aligned' => $this->isDkimAligned($policyEvaluated, $authResults),
                'dkim_result' => $this->getDkimResult($policyEvaluated, $authResults),
                'spf_aligned' => $this->isSpfAligned($policyEvaluated, $authResults),
                'spf_result' => $this->getSpfResult($policyEvaluated, $authResults),
            ]);
        }

        return $records;
    }

    /**
     * Check if DKIM is aligned.
     */
    private function isDkimAligned(SimpleXMLElement $policyEvaluated, ?SimpleXMLElement $authResults): bool
    {
        // Check policy_evaluated first
        $policyDkim = (string) ($policyEvaluated->xpath('./*[local-name()="dkim"]')[0] ?? '');
        if ($policyDkim === 'pass') {
            return true;
        }

        // Fallback to auth_results if available
        if ($authResults) {
            $dkimResults = $authResults->xpath('./*[local-name()="dkim"]');
            foreach ($dkimResults as $dkim) {
                $result = (string) ($dkim->xpath('./*[local-name()="result"]')[0] ?? '');
                if ($result === 'pass') {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get DKIM result.
     */
    private function getDkimResult(SimpleXMLElement $policyEvaluated, ?SimpleXMLElement $authResults): string
    {
        // Check policy_evaluated first
        $policyDkim = (string) ($policyEvaluated->xpath('./*[local-name()="dkim"]')[0] ?? '');
        if (!empty($policyDkim)) {
            return $policyDkim;
        }

        // Fallback to auth_results if available
        if ($authResults) {
            $dkimResults = $authResults->xpath('./*[local-name()="dkim"]');
            foreach ($dkimResults as $dkim) {
                $result = (string) ($dkim->xpath('./*[local-name()="result"]')[0] ?? '');
                if (!empty($result)) {
                    return $result;
                }
            }
        }
        
        return 'fail';
    }

    /**
     * Check if SPF is aligned.
     */
    private function isSpfAligned(SimpleXMLElement $policyEvaluated, ?SimpleXMLElement $authResults): bool
    {
        // Check policy_evaluated first
        $policySpf = (string) ($policyEvaluated->xpath('./*[local-name()="spf"]')[0] ?? '');
        if ($policySpf === 'pass') {
            return true;
        }

        // Fallback to auth_results if available
        if ($authResults) {
            $spfResults = $authResults->xpath('./*[local-name()="spf"]');
            foreach ($spfResults as $spf) {
                $result = (string) ($spf->xpath('./*[local-name()="result"]')[0] ?? '');
                if ($result === 'pass') {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get SPF result.
     */
    private function getSpfResult(SimpleXMLElement $policyEvaluated, ?SimpleXMLElement $authResults): string
    {
        // Check policy_evaluated first
        $policySpf = (string) ($policyEvaluated->xpath('./*[local-name()="spf"]')[0] ?? '');
        if (!empty($policySpf)) {
            return $policySpf;
        }

        // Fallback to auth_results if available
        if ($authResults) {
            $spfResults = $authResults->xpath('./*[local-name()="spf"]');
            foreach ($spfResults as $spf) {
                $result = (string) ($spf->xpath('./*[local-name()="result"]')[0] ?? '');
                if (!empty($result)) {
                    return $result;
                }
            }
        }
        
        return 'fail';
    }
} 