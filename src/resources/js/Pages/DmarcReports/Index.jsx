import { Head } from '@inertiajs/react';
import React from 'react';
import { Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042'];

export default function Index({ reports, statistics }) {
    // 認証成功・失敗のデータ
    const authData = [
        { name: '認証成功', value: statistics.auth_success_count, color: '#00C49F' },
        { name: '認証失敗', value: statistics.auth_failure_count, color: '#FF8042' },
    ];

    // 組織別レポート数のデータ
    const orgData = reports.reduce((acc, report) => {
        const org = report.org_name;
        acc[org] = (acc[org] || 0) + 1;
        return acc;
    }, {});

    const orgChartData = Object.entries(orgData).map(([org, count]) => ({
        name: org,
        reports: count,
    }));

    return (
        <>
            <Head title="DMARC Reports Dashboard" />
            
            <div className="min-h-screen bg-gray-100">
                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 text-gray-900">
                                <h1 className="text-3xl font-bold text-gray-900 mb-8">
                                    DMARC Reports Dashboard
                                </h1>

                                {/* 統計情報 */}
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                                    <div className="bg-blue-50 p-4 rounded-lg">
                                        <h3 className="text-lg font-semibold text-blue-900">総レポート数</h3>
                                        <p className="text-3xl font-bold text-blue-600">{statistics.total_reports}</p>
                                    </div>
                                    <div className="bg-green-50 p-4 rounded-lg">
                                        <h3 className="text-lg font-semibold text-green-900">総レコード数</h3>
                                        <p className="text-3xl font-bold text-green-600">{statistics.total_records}</p>
                                    </div>
                                    <div className="bg-purple-50 p-4 rounded-lg">
                                        <h3 className="text-lg font-semibold text-purple-900">総メール数</h3>
                                        <p className="text-3xl font-bold text-purple-600">{statistics.total_emails}</p>
                                    </div>
                                    <div className="bg-orange-50 p-4 rounded-lg">
                                        <h3 className="text-lg font-semibold text-orange-900">認証成功率</h3>
                                        <p className="text-3xl font-bold text-orange-600">
                                            {statistics.total_emails > 0 
                                                ? Math.round((statistics.auth_success_count / statistics.total_emails) * 100)
                                                : 0}%
                                        </p>
                                    </div>
                                </div>

                                {/* グラフ */}
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                                    {/* 認証成功・失敗の円グラフ */}
                                    <div className="bg-white p-6 rounded-lg shadow">
                                        <h3 className="text-xl font-semibold mb-4">認証結果</h3>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <PieChart>
                                                <Pie
                                                    data={authData}
                                                    cx="50%"
                                                    cy="50%"
                                                    labelLine={false}
                                                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                                    outerRadius={80}
                                                    fill="#8884d8"
                                                    dataKey="value"
                                                >
                                                    {authData.map((entry, index) => (
                                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                                    ))}
                                                </Pie>
                                                <Tooltip />
                                            </PieChart>
                                        </ResponsiveContainer>
                                    </div>

                                    {/* 組織別レポート数の棒グラフ */}
                                    <div className="bg-white p-6 rounded-lg shadow">
                                        <h3 className="text-xl font-semibold mb-4">組織別レポート数</h3>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <BarChart data={orgChartData}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="name" />
                                                <YAxis />
                                                <Tooltip />
                                                <Legend />
                                                <Bar dataKey="reports" fill="#8884d8" />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </div>
                                </div>

                                {/* レポート一覧テーブル */}
                                <div className="bg-white rounded-lg shadow">
                                    <div className="px-6 py-4 border-b border-gray-200">
                                        <h3 className="text-xl font-semibold">DMARC Reports</h3>
                                    </div>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Organization
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Report ID
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Date Range
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Records
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Total Emails
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-200">
                                                {reports.map((report) => (
                                                    <tr key={report.id} className="hover:bg-gray-50">
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            {report.org_name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {report.report_id}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {new Date(report.begin_date).toLocaleDateString()} - {new Date(report.end_date).toLocaleDateString()}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {report.records_count}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {report.records?.reduce((sum, record) => sum + record.count, 0) || 0}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
} 