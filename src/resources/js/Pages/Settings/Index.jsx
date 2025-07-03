import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import React from 'react';

export default function Settings({ settings, storageInfo }) {
    return (
        <>
            <Head title="Settings" />
            
            <div className="container mx-auto px-4 py-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-8">Settings</h1>
                
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* システム設定 */}
                    <Card>
                        <CardHeader>
                            <CardTitle>システム設定</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">アプリ名</span>
                                    <span className="text-sm text-gray-900">{settings.system.app_name}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">環境</span>
                                    <span className={`text-sm px-2 py-1 rounded-full ${
                                        settings.system.app_env === 'production' 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-yellow-100 text-yellow-800'
                                    }`}>
                                        {settings.system.app_env}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">デバッグモード</span>
                                    <span className={`text-sm px-2 py-1 rounded-full ${
                                        settings.system.app_debug 
                                            ? 'bg-red-100 text-red-800' 
                                            : 'bg-green-100 text-green-800'
                                    }`}>
                                        {settings.system.app_debug ? 'ON' : 'OFF'}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">タイムゾーン</span>
                                    <span className="text-sm text-gray-900">{settings.system.timezone}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">ロケール</span>
                                    <span className="text-sm text-gray-900">{settings.system.locale}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    {/* DMARC設定 */}
                    <Card>
                        <CardHeader>
                            <CardTitle>DMARC設定</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">レポートディレクトリ</span>
                                    <span className="text-sm text-gray-900">{settings.dmarc.reports_directory}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">1ページあたりの最大件数</span>
                                    <span className="text-sm text-gray-900">{settings.dmarc.max_records_per_page}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">インポートバッチサイズ</span>
                                    <span className="text-sm text-gray-900">{settings.dmarc.import_batch_size}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    {/* ストレージ設定 */}
                    <Card>
                        <CardHeader>
                            <CardTitle>ストレージ設定</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">デフォルトディスク</span>
                                    <span className="text-sm text-gray-900">{settings.storage.disk}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">DMARCディスク</span>
                                    <span className="text-sm text-gray-900">{settings.storage.dmarc_disk}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">DMARCパス</span>
                                    <span className="text-sm text-gray-900">{settings.storage.dmarc_path}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    {/* キャッシュ設定 */}
                    <Card>
                        <CardHeader>
                            <CardTitle>キャッシュ設定</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">デフォルトドライバー</span>
                                    <span className="text-sm text-gray-900">{settings.cache.default}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm font-medium text-gray-700">TTL（秒）</span>
                                    <span className="text-sm text-gray-900">{settings.cache.ttl}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                
                {/* ストレージ情報 */}
                <Card className="mt-8">
                    <CardHeader>
                        <CardTitle>ストレージ情報</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-blue-600">{storageInfo.total_files}</div>
                                <div className="text-sm text-gray-500">総ファイル数</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-green-600">{storageInfo.total_size}</div>
                                <div className="text-sm text-gray-500">総サイズ</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-purple-600">
                                    {storageInfo.last_import ? '✓' : '✗'}
                                </div>
                                <div className="text-sm text-gray-500">
                                    {storageInfo.last_import ? '最終インポート' : 'インポートなし'}
                                </div>
                                {storageInfo.last_import && (
                                    <div className="text-xs text-gray-400 mt-1">
                                        {storageInfo.last_import}
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                {/* クイックアクション */}
                <Card className="mt-8">
                    <CardHeader>
                        <CardTitle>クイックアクション</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <div className="text-center">
                                    <div className="text-lg font-medium text-gray-900">キャッシュクリア</div>
                                    <div className="text-sm text-gray-500">アプリケーションキャッシュをクリア</div>
                                </div>
                            </button>
                            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <div className="text-center">
                                    <div className="text-lg font-medium text-gray-900">設定リロード</div>
                                    <div className="text-sm text-gray-500">設定ファイルを再読み込み</div>
                                </div>
                            </button>
                            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <div className="text-center">
                                    <div className="text-lg font-medium text-gray-900">ログ確認</div>
                                    <div className="text-sm text-gray-500">アプリケーションログを表示</div>
                                </div>
                            </button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
} 