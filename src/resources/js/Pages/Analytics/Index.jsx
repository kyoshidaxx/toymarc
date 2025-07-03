import Layout from '@/components/Layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/react';
import React, { useState } from 'react';
import { Bar, BarChart, CartesianGrid, Cell, Line, LineChart, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

export default function Analytics({ analytics }) {
    const [dateRange, setDateRange] = useState({
        start: analytics.dateRange.start,
        end: analytics.dateRange.end,
    });

    const handleDateChange = (field, value) => {
        const newRange = { ...dateRange, [field]: value };
        setDateRange(newRange);
        
        router.get('/analytics', {
            start_date: newRange.start,
            end_date: newRange.end,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    // 認証結果データ
    const authData = [
        { name: 'DKIM+SPF成功', value: analytics.authStats.both_success },
        { name: 'DKIMのみ成功', value: analytics.authStats.dkim_only },
        { name: 'SPFのみ成功', value: analytics.authStats.spf_only },
        { name: '両方失敗', value: analytics.authStats.both_failed },
    ];

    // 日別統計データ
    const dailyData = analytics.dailyStats.map(stat => ({
        date: new Date(stat.date).toLocaleDateString(),
        reports: stat.reports_count,
        emails: stat.emails_count,
    }));

    // 送信元IPデータ
    const sourceIpData = analytics.topSourceIps.map(ip => ({
        ip: ip.source_ip,
        emails: ip.total_emails,
        successRate: ip.success_rate,
    }));

    return (
        <Layout>
            <Head title="Analytics" />
            
            <div className="container mx-auto px-4 py-8">
                <div className="flex justify-between items-center mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">Analytics</h1>
                    
                    {/* 日付範囲選択 */}
                    <div className="flex gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                開始日
                            </label>
                            <input
                                type="date"
                                value={dateRange.start}
                                onChange={(e) => handleDateChange('start', e.target.value)}
                                className="border border-gray-300 rounded-md px-3 py-2"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                終了日
                            </label>
                            <input
                                type="date"
                                value={dateRange.end}
                                onChange={(e) => handleDateChange('end', e.target.value)}
                                className="border border-gray-300 rounded-md px-3 py-2"
                            />
                        </div>
                    </div>
                </div>
                
                {/* 日別トレンド */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle>日別トレンド</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={400}>
                            <LineChart data={dailyData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis yAxisId="left" />
                                <YAxis yAxisId="right" orientation="right" />
                                <Tooltip />
                                <Line yAxisId="left" type="monotone" dataKey="reports" stroke="#8884d8" name="レポート数" />
                                <Line yAxisId="right" type="monotone" dataKey="emails" stroke="#82ca9d" name="メール数" />
                            </LineChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
                
                {/* 認証結果分析 */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>認証結果分布</CardTitle>
                        </CardHeader>
                        <CardContent>
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
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>送信元IP別統計（上位10件）</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={sourceIpData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="ip" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="emails" fill="#8884d8" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>
                
                {/* 組織別統計 */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle>組織別統計（上位10件）</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            組織名
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            レポート数
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            メール数
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {analytics.orgStats.map((org, index) => (
                                        <tr key={index}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {org.org_name}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {org.reports_count.toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {org.emails_count.toLocaleString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
                
                {/* ポリシー別統計 */}
                <Card>
                    <CardHeader>
                        <CardTitle>ポリシー別統計</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {analytics.policyStats.map((policy, index) => (
                                <div key={index} className="p-4 border rounded-lg">
                                    <h3 className="font-medium text-gray-900 mb-2">
                                        ポリシー: {policy.policy_p}
                                    </h3>
                                    <div className="space-y-1">
                                        <p className="text-sm text-gray-500">
                                            レポート数: {policy.reports_count.toLocaleString()}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            メール数: {policy.emails_count.toLocaleString()}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </Layout>
    );
} 