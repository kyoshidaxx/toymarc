import Layout from '@/components/Layout';
import { Head, Link } from '@inertiajs/react';
import React from 'react';
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

export default function Show({ report }) {
    // 認証結果の集計
    const authStats = report.records.reduce((acc, record) => {
        if (record.dkim_aligned || record.spf_aligned) {
            acc.success += record.count;
        } else {
            acc.failure += record.count;
        }
        return acc;
    }, { success: 0, failure: 0 });

    // IPアドレス別のデータ
    const ipData = report.records.map(record => ({
        ip: record.source_ip,
        count: record.count,
        dkim: record.dkim_aligned ? 'Success' : 'Failed',
        spf: record.spf_aligned ? 'Success' : 'Failed',
    }));

    return (
        <Layout>
            <Head title={`DMARC Report - ${report.report_id}`} />
            
            <div className="container mx-auto px-4 py-8">
                {/* 戻るボタン */}
                <div className="mb-6">
                    <Link
                        href="/dmarc-reports"
                        className="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        ← Back to Reports
                    </Link>
                </div>

                <h1 className="text-3xl font-bold text-gray-900 mb-8">
                    DMARC Report Details
                </h1>

                {/* レポート基本情報 */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div className="bg-blue-50 p-4 rounded-lg">
                        <h3 className="text-lg font-semibold text-blue-900">Organization</h3>
                        <p className="text-xl font-bold text-blue-600">{report.org_name}</p>
                    </div>
                    <div className="bg-green-50 p-4 rounded-lg">
                        <h3 className="text-lg font-semibold text-green-900">Report ID</h3>
                        <p className="text-sm font-mono text-green-600">{report.report_id}</p>
                    </div>
                    <div className="bg-purple-50 p-4 rounded-lg">
                        <h3 className="text-lg font-semibold text-purple-900">Date Range</h3>
                        <p className="text-sm text-purple-600">
                            {new Date(report.begin_date).toLocaleDateString()} - {new Date(report.end_date).toLocaleDateString()}
                        </p>
                    </div>
                    <div className="bg-orange-50 p-4 rounded-lg">
                        <h3 className="text-lg font-semibold text-orange-900">Total Records</h3>
                        <p className="text-3xl font-bold text-orange-600">{report.records.length}</p>
                    </div>
                </div>

                {/* 認証結果のグラフ */}
                <div className="bg-white p-6 rounded-lg shadow mb-8">
                    <h3 className="text-xl font-semibold mb-4">Authentication Results</h3>
                    <ResponsiveContainer width="100%" height={300}>
                        <BarChart data={[
                            { name: 'Success', count: authStats.success, fill: '#00C49F' },
                            { name: 'Failure', count: authStats.failure, fill: '#FF8042' },
                        ]}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="name" />
                            <YAxis />
                            <Tooltip />
                            <Legend />
                            <Bar dataKey="count" fill="#8884d8" />
                        </BarChart>
                    </ResponsiveContainer>
                </div>

                {/* レコード一覧テーブル */}
                <div className="bg-white rounded-lg shadow">
                    <div className="px-6 py-4 border-b border-gray-200">
                        <h3 className="text-xl font-semibold">DMARC Records</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Source IP
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Count
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        DKIM
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        SPF
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Disposition
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reason
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {report.records.map((record) => (
                                    <tr key={record.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                            {record.source_ip}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {record.count}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                record.dkim_aligned 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-red-100 text-red-800'
                                            }`}>
                                                {record.dkim_aligned ? 'Success' : 'Failed'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                record.spf_aligned 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-red-100 text-red-800'
                                            }`}>
                                                {record.spf_aligned ? 'Success' : 'Failed'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {record.disposition || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {record.reason || 'N/A'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* 生データ（開発用） */}
                {process.env.NODE_ENV === 'development' && (
                    <div className="mt-8 bg-white rounded-lg shadow">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-xl font-semibold">Raw Data (Development)</h3>
                        </div>
                        <div className="p-6">
                            <pre className="text-xs bg-gray-100 p-4 rounded overflow-auto max-h-96">
                                {JSON.stringify(report.raw_data, null, 2)}
                            </pre>
                        </div>
                    </div>
                )}
            </div>
        </Layout>
    );
} 