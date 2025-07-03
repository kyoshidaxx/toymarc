<?php

namespace Tests\Feature;

use App\Models\DmarcReport;
use App\Services\DmarcReportParserService;
use App\Services\DmarcReportParseException;
use Tests\TestCase;

class DmarcReportParserServiceTest extends TestCase
{
    private DmarcReportParserService $parserService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parserService = new DmarcReportParserService();
    }

    public function test_parse_valid_xml_report(): void
    {
        // Arrange
        $xmlContent = $this->getValidXmlContent();
        
        // Act
        $report = $this->parserService->parseXmlReport($xmlContent);
        
        // Assert
        $this->assertInstanceOf(DmarcReport::class, $report);
        $this->assertEquals('example.com', $report->org_name);
        $this->assertEquals('dmarc@example.com', $report->email);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $report->report_id);
        $this->assertEquals('example.com', $report->policy_domain);
        $this->assertEquals('none', $report->policy_p);
        $this->assertEquals(100, $report->policy_pct);
        $this->assertCount(1, $report->records);
        
        $record = $report->records->first();
        $this->assertEquals('192.168.1.1', $record->source_ip);
        $this->assertEquals(10, $record->count);
        $this->assertEquals('none', $record->disposition);
        $this->assertTrue($record->dkim_aligned);
        $this->assertEquals('pass', $record->dkim_result);
        $this->assertFalse($record->spf_aligned);
        $this->assertEquals('fail', $record->spf_result);
    }

    public function test_parse_invalid_xml_throws_exception(): void
    {
        // Arrange
        $invalidXml = '<invalid>xml</content>';
        
        // Act & Assert
        $this->expectException(DmarcReportParseException::class);
        $this->parserService->parseXmlReport($invalidXml);
    }

    public function test_parse_xml_without_metadata_throws_exception(): void
    {
        // Arrange
        $xmlWithoutMetadata = '<?xml version="1.0" encoding="UTF-8"?>
        <feedback xmlns="http://dmarc.org/dmarc-xml/1.0">
            <record>
                <row>
                    <source_ip>192.168.1.1</source_ip>
                    <count>10</count>
                </row>
            </record>
        </feedback>';
        
        // Act & Assert
        $this->expectException(DmarcReportParseException::class);
        $this->parserService->parseXmlReport($xmlWithoutMetadata);
    }

    public function test_parse_xml_without_policy_throws_exception(): void
    {
        // Arrange
        $xmlWithoutPolicy = '<?xml version="1.0" encoding="UTF-8"?>
        <feedback xmlns="http://dmarc.org/dmarc-xml/1.0">
            <report_metadata>
                <org_name>example.com</org_name>
                <email>dmarc@example.com</email>
                <report_id>2024-01-01T00:00:00+00:00</report_id>
                <date_range>
                    <begin>1704067200</begin>
                    <end>1704153600</end>
                </date_range>
            </report_metadata>
        </feedback>';
        
        // Act & Assert
        $this->expectException(DmarcReportParseException::class);
        $this->parserService->parseXmlReport($xmlWithoutPolicy);
    }

    private function getValidXmlContent(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
        <feedback xmlns="http://dmarc.org/dmarc-xml/1.0">
            <report_metadata>
                <org_name>example.com</org_name>
                <email>dmarc@example.com</email>
                <report_id>2024-01-01T00:00:00+00:00</report_id>
                <date_range>
                    <begin>1704067200</begin>
                    <end>1704153600</end>
                </date_range>
            </report_metadata>
            <policy_published>
                <domain>example.com</domain>
                <p>none</p>
                <pct>100</pct>
            </policy_published>
            <record>
                <row>
                    <source_ip>192.168.1.1</source_ip>
                    <count>10</count>
                </row>
                <identifiers>
                    <header_from>example.com</header_from>
                </identifiers>
                <auth_results>
                    <dkim>
                        <domain>example.com</domain>
                        <result>pass</result>
                    </dkim>
                    <spf>
                        <domain>example.com</domain>
                        <result>fail</result>
                    </spf>
                </auth_results>
                <policy_evaluated>
                    <disposition>none</disposition>
                </policy_evaluated>
            </record>
        </feedback>';
    }
} 