import Layout from '@/components/Layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042'];

export default function Index({ reports = [], statistics = {} }) {
    // デフォルト値を設定
    const safeStatistics = {
        total_reports: statistics.total_reports || 0,
        total_records: statistics.total_records || 0,
        total_emails: statistics.total_emails || 0,
        auth_success_count: statistics.auth_success_count || 0,
        auth_failure_count: statistics.auth_failure_count || 0,
    };

    // 認証成功・失敗のデータ
    const authData = [
        { name: '認証成功', value: safeStatistics.auth_success_count, color: '#00C49F' },
        { name: '認証失敗', value: safeStatistics.auth_failure_count, color: '#FF8042' },
    ];

    // 組織別レポート数のデータ
    const orgData = reports.reduce((acc, report) => {
        const org = report.org_name || 'Unknown';
        acc[org] = (acc[org] || 0) + 1;
        return acc;
    }, {});

    const orgChartData = Object.entries(orgData).map(([org, count]) => ({
        name: org,
        reports: count,
    }));

    return (
        <Layout>
            <Head title="DMARC Reports Dashboard" />
            
            <div className="container mx-auto px-4 py-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-8">
                    DMARC Reports Dashboard
                </h1>

                {/* 統計情報 */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">総レポート数</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{safeStatistics.total_reports.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                全期間のレポート数
                            </p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">総レコード数</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{safeStatistics.total_records.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                全期間のレコード数
                            </p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">総メール数</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{safeStatistics.total_emails.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                全期間のメール数
                            </p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">認証成功率</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {safeStatistics.total_emails > 0 
                                    ? Math.round((safeStatistics.auth_success_count / safeStatistics.total_emails) * 100)
                                    : 0}%
                            </div>
                            <p className="text-xs text-muted-foreground">
                                認証成功メール数
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* グラフ */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {/* 認証成功・失敗の円グラフ */}
                    <Card>
                        <CardHeader>
                            <CardTitle>認証結果</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {safeStatistics.total_emails > 0 ? (
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
                            ) : (
                                <div className="flex items-center justify-center h-[300px] text-gray-500">
                                    データがありません
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* 組織別レポート数の棒グラフ */}
                    <Card>
                        <CardHeader>
                            <CardTitle>組織別レポート数</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {orgChartData.length > 0 ? (
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
                            ) : (
                                <div className="flex items-center justify-center h-[300px] text-gray-500">
                                    データがありません
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* レポート一覧テーブル */}
                <Card>
                    <CardHeader>
                        <CardTitle>DMARC Reports</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {reports.length > 0 ? (
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
                                                    {report.org_name || 'Unknown'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {report.report_id || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {report.begin_date && report.end_date ? (
                                                        `${new Date(report.begin_date).toLocaleDateString()} - ${new Date(report.end_date).toLocaleDateString()}`
                                                    ) : (
                                                        'N/A'
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {report.records_count || 0}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {report.records?.reduce((sum, record) => sum + (record.count || 0), 0) || 0}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <div className="text-gray-500 text-lg mb-4">DMARCレポートがありません</div>
                                <p className="text-gray-400">設定ページからレポートをインポートしてください。</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </Layout>
    );
} 