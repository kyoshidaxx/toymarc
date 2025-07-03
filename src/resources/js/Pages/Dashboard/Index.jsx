import Layout from '@/components/Layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042'];

export default function Dashboard({ statistics, recentActivity, auth }) {
    const { auth: pageAuth } = usePage();
    
    // デバッグ用：認証データをコンソールに出力
    useEffect(() => {
        console.log('Dashboard - Page auth prop:', auth);
        console.log('Dashboard - usePage auth:', pageAuth);
        console.log('Dashboard - Auth user:', auth?.user || pageAuth?.user);
        console.log('Dashboard - Page props:', { statistics, recentActivity });
    }, [auth, pageAuth, statistics, recentActivity]);

    // 認証データの優先順位：明示的に渡されたデータ > usePageのデータ
    const authData = auth || pageAuth;

    const policyData = Object.entries(statistics.policy_breakdown).map(([policy, count]) => ({
        name: policy,
        value: count,
    }));

    const organizationData = statistics.top_organizations.map(org => ({
        name: org.org_name,
        reports: org.report_count,
    }));

    return (
        <Layout>
            <Head title="Dashboard" />
            
            <div className="container mx-auto px-4 py-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-8">Dashboard</h1>
                
                {/* デバッグ情報（開発時のみ表示） */}
                {process.env.NODE_ENV === 'development' && (
                    <div className="mb-4 p-4 bg-yellow-100 border border-yellow-400 rounded">
                        <h3 className="font-bold text-yellow-800">デバッグ情報</h3>
                        <pre className="text-xs text-yellow-700">
                            Page Auth: {JSON.stringify(auth, null, 2)}
                        </pre>
                        <pre className="text-xs text-yellow-700">
                            usePage Auth: {JSON.stringify(pageAuth, null, 2)}
                        </pre>
                        <pre className="text-xs text-yellow-700">
                            Final Auth: {JSON.stringify(authData, null, 2)}
                        </pre>
                    </div>
                )}
                
                {/* 統計カード */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">総レポート数</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.total_reports.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                最近30日: {statistics.recent_reports.toLocaleString()}
                            </p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">総メール数</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.total_emails.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                最近30日: {statistics.recent_emails.toLocaleString()}
                            </p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">認証成功率</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.auth_success_rate}%</div>
                            <p className="text-xs text-muted-foreground">
                                総レコード数: {statistics.total_records.toLocaleString()}
                            </p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">組織数</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.top_organizations.length}</div>
                            <p className="text-xs text-muted-foreground">
                                上位5組織の統計
                            </p>
                        </CardContent>
                    </Card>
                </div>
                
                {/* グラフセクション */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {/* ポリシー別分布 */}
                    <Card>
                        <CardHeader>
                            <CardTitle>ポリシー別分布</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={policyData}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {policyData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                    
                    {/* 組織別レポート数 */}
                    <Card>
                        <CardHeader>
                            <CardTitle>組織別レポート数（上位5件）</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={organizationData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="reports" fill="#8884d8" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>
                
                {/* 最近のアクティビティ */}
                <Card>
                    <CardHeader>
                        <CardTitle>最近のアクティビティ</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {recentActivity.map((activity) => (
                                <div key={activity.id} className="flex items-center justify-between p-4 border rounded-lg">
                                    <div className="flex-1">
                                        <h3 className="font-medium text-gray-900">{activity.org_name}</h3>
                                        <p className="text-sm text-gray-500">
                                            レポートID: {activity.report_id}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            期間: {new Date(activity.begin_date).toLocaleDateString()} - {new Date(activity.end_date).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-medium text-gray-900">
                                            {activity.total_emails.toLocaleString()} メール
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            {activity.records_count} レコード
                                        </p>
                                        <p className="text-xs text-gray-400">
                                            {new Date(activity.created_at).toLocaleString()}
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