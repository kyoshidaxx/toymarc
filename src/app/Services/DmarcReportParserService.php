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
            $xml->registerXPathNamespace('dmarc', 'http://dmarc.org/dmarc-xml/1.0');

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
                'raw_data' => json_decode($xmlContent, true),
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
     */
    private function parseReportMetadata(SimpleXMLElement $xml): array
    {
        $metadata = $xml->xpath('//dmarc:report_metadata')[0] ?? null;
        if (!$metadata) {
            throw new DmarcReportParseException('レポートメタデータが見つかりません');
        }

        $dateRange = $metadata->xpath('.//dmarc:date_range')[0] ?? null;
        if (!$dateRange) {
            throw new DmarcReportParseException('日付範囲が見つかりません');
        }

        return [
            'org_name' => (string) ($metadata->xpath('.//dmarc:org_name')[0] ?? ''),
            'email' => (string) ($metadata->xpath('.//dmarc:email')[0] ?? ''),
            'report_id' => (string) ($metadata->xpath('.//dmarc:report_id')[0] ?? ''),
            'begin_date' => date('Y-m-d H:i:s', (int) ($dateRange->xpath('.//dmarc:begin')[0] ?? 0)),
            'end_date' => date('Y-m-d H:i:s', (int) ($dateRange->xpath('.//dmarc:end')[0] ?? 0)),
        ];
    }

    /**
     * Parse policy published from XML.
     */
    private function parsePolicyPublished(SimpleXMLElement $xml): array
    {
        $policy = $xml->xpath('//dmarc:policy_published')[0] ?? null;
        if (!$policy) {
            throw new DmarcReportParseException('ポリシー情報が見つかりません');
        }

        return [
            'domain' => (string) ($policy->xpath('.//dmarc:domain')[0] ?? ''),
            'p' => (string) ($policy->xpath('.//dmarc:p')[0] ?? 'none'),
            'pct' => (int) ($policy->xpath('.//dmarc:pct')[0] ?? 100),
        ];
    }

    /**
     * Parse records from XML.
     */
    private function parseRecords(SimpleXMLElement $xml): array
    {
        $records = [];
        $recordNodes = $xml->xpath('//dmarc:record');

        foreach ($recordNodes as $recordNode) {
            $row = $recordNode->xpath('.//dmarc:row')[0] ?? null;
            $policyEvaluated = $recordNode->xpath('.//dmarc:policy_evaluated')[0] ?? null;
            $identifiers = $recordNode->xpath('.//dmarc:identifiers')[0] ?? null;
            $authResults = $recordNode->xpath('.//dmarc:auth_results')[0] ?? null;

            if (!$row || !$policyEvaluated || !$identifiers || !$authResults) {
                continue;
            }

            $records[] = new DmarcRecord([
                'source_ip' => (string) ($row->xpath('.//dmarc:source_ip')[0] ?? ''),
                'count' => (int) ($row->xpath('.//dmarc:count')[0] ?? 0),
                'disposition' => (string) ($policyEvaluated->xpath('.//dmarc:disposition')[0] ?? ''),
                'dkim_aligned' => $this->isDkimAligned($authResults),
                'dkim_result' => $this->getDkimResult($authResults),
                'spf_aligned' => $this->isSpfAligned($authResults),
                'spf_result' => $this->getSpfResult($authResults),
            ]);
        }

        return $records;
    }

    /**
     * Check if DKIM is aligned.
     */
    private function isDkimAligned(SimpleXMLElement $authResults): bool
    {
        $dkimResults = $authResults->xpath('.//dmarc:dkim');
        foreach ($dkimResults as $dkim) {
            $result = (string) ($dkim->xpath('.//dmarc:result')[0] ?? '');
            if ($result === 'pass') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get DKIM result.
     */
    private function getDkimResult(SimpleXMLElement $authResults): string
    {
        $dkimResults = $authResults->xpath('.//dmarc:dkim');
        foreach ($dkimResults as $dkim) {
            $result = (string) ($dkim->xpath('.//dmarc:result')[0] ?? '');
            if ($result === 'pass') {
                return 'pass';
            }
        }
        return 'fail';
    }

    /**
     * Check if SPF is aligned.
     */
    private function isSpfAligned(SimpleXMLElement $authResults): bool
    {
        $spfResults = $authResults->xpath('.//dmarc:spf');
        foreach ($spfResults as $spf) {
            $result = (string) ($spf->xpath('.//dmarc:result')[0] ?? '');
            if ($result === 'pass') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get SPF result.
     */
    private function getSpfResult(SimpleXMLElement $authResults): string
    {
        $spfResults = $authResults->xpath('.//dmarc:spf');
        foreach ($spfResults as $spf) {
            $result = (string) ($spf->xpath('.//dmarc:result')[0] ?? '');
            if ($result === 'pass') {
                return 'pass';
            }
        }
        return 'fail';
    }
} 