import Layout from '@/components/Layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/react';
import React, { useEffect, useRef, useState } from 'react';

export default function Settings({ settings, storageInfo, flash }) {
    const [loading, setLoading] = useState({
        uploadReports: false,
        clearCache: false,
        reloadConfig: false,
        viewLogs: false,
        cleanupStorage: false
    });

    const [showLogModal, setShowLogModal] = useState(false);
    const [showUploadModal, setShowUploadModal] = useState(false);
    const [logData, setLogData] = useState(null);
    const [logFiles, setLogFiles] = useState([]);
    const [selectedLogFile, setSelectedLogFile] = useState('laravel.log');
    const [logLines, setLogLines] = useState(100);
    const [selectedFiles, setSelectedFiles] = useState([]);
    const [dragActive, setDragActive] = useState(false);
    const fileInputRef = useRef(null);

    // フラッシュメッセージの表示
    useEffect(() => {
        if (flash?.success) {
            alert(flash.success);
        }
        if (flash?.error) {
            alert('エラー: ' + flash.error);
        }
        if (flash?.details) {
            const errorDetails = Array.isArray(flash.details) ? flash.details.join('\n') : flash.details;
            alert('詳細エラー:\n' + errorDetails);
        }
    }, [flash]);

    const handleUploadReports = async () => {
        if (selectedFiles.length === 0) {
            alert('ファイルを選択してください');
            return;
        }

        setLoading(prev => ({ ...prev, uploadReports: true }));
        
        const formData = new FormData();
        selectedFiles.forEach(file => {
            formData.append('files[]', file);
        });

        router.post('/settings/upload-reports', formData, {
            onFinish: () => {
                setLoading(prev => ({ ...prev, uploadReports: false }));
                setShowUploadModal(false);
                setSelectedFiles([]);
            }
        });
    };

    const handleFileSelect = (event) => {
        const files = Array.from(event.target.files);
        setSelectedFiles(prev => [...prev, ...files]);
    };

    const handleDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === "dragenter" || e.type === "dragover") {
            setDragActive(true);
        } else if (e.type === "dragleave") {
            setDragActive(false);
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            const files = Array.from(e.dataTransfer.files);
            setSelectedFiles(prev => [...prev, ...files]);
        }
    };

    const removeFile = (index) => {
        setSelectedFiles(prev => prev.filter((_, i) => i !== index));
    };

    const clearFiles = () => {
        setSelectedFiles([]);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleClearCache = async () => {
        setLoading(prev => ({ ...prev, clearCache: true }));
        router.post('/settings/clear-cache', {}, {
            onFinish: () => {
                setLoading(prev => ({ ...prev, clearCache: false }));
            }
        });
    };

    const handleReloadConfig = () => {
        setLoading(prev => ({ ...prev, reloadConfig: true }));
        // 設定リロードの実装（必要に応じてAPI追加）
        setTimeout(() => {
            alert('設定がリロードされました');
            setLoading(prev => ({ ...prev, reloadConfig: false }));
        }, 1000);
    };

    const handleViewLogs = async () => {
        setLoading(prev => ({ ...prev, viewLogs: true }));
        try {
            // ログファイル一覧を取得
            const filesResponse = await fetch('/settings/log-files');
            const filesResult = await filesResponse.json();
            
            if (filesResult.success) {
                setLogFiles(filesResult.data);
                
                // デフォルトのログファイルを読み込み
                const logResponse = await fetch(`/settings/logs?file=${selectedLogFile}&lines=${logLines}`);
                const logResult = await logResponse.json();
                
                if (logResult.success) {
                    setLogData(logResult.data);
                    setShowLogModal(true);
                } else {
                    alert('ログの読み込みに失敗しました: ' + logResult.message);
                }
            } else {
                alert('ログファイル一覧の取得に失敗しました: ' + filesResult.message);
            }
        } catch (error) {
            alert('エラーが発生しました: ' + error.message);
        } finally {
            setLoading(prev => ({ ...prev, viewLogs: false }));
        }
    };

    const handleCleanupStorage = async () => {
        if (!confirm('30日以上古いDMARCレポートファイルを削除します。続行しますか？')) {
            return;
        }

        setLoading(prev => ({ ...prev, cleanupStorage: true }));
        router.post('/settings/cleanup-storage', {}, {
            onFinish: () => {
                setLoading(prev => ({ ...prev, cleanupStorage: false }));
            }
        });
    };

    const loadLogFile = async (filename) => {
        try {
            const response = await fetch(`/settings/logs?file=${filename}&lines=${logLines}`);
            const result = await response.json();
            
            if (result.success) {
                setLogData(result.data);
                setSelectedLogFile(filename);
            } else {
                alert('ログの読み込みに失敗しました: ' + result.message);
            }
        } catch (error) {
            alert('エラーが発生しました: ' + error.message);
        }
    };

    const refreshLogs = async () => {
        await loadLogFile(selectedLogFile);
    };

    return (
        <Layout>
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
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <button 
                                onClick={() => setShowUploadModal(true)}
                                disabled={loading.uploadReports}
                                className={`p-4 border border-gray-300 rounded-lg transition-colors ${
                                    loading.uploadReports 
                                        ? 'bg-gray-100 cursor-not-allowed' 
                                        : 'hover:bg-blue-50 hover:border-blue-300'
                                }`}
                            >
                                <div className="text-center">
                                    <div className="text-lg font-medium text-gray-900">
                                        {loading.uploadReports ? 'アップロード中...' : 'レポートアップロード'}
                                    </div>
                                    <div className="text-sm text-gray-500">DMARCレポートファイルをアップロード</div>
                                </div>
                            </button>
                            
                            <button 
                                onClick={handleClearCache}
                                disabled={loading.clearCache}
                                className={`p-4 border border-gray-300 rounded-lg transition-colors ${
                                    loading.clearCache 
                                        ? 'bg-gray-100 cursor-not-allowed' 
                                        : 'hover:bg-green-50 hover:border-green-300'
                                }`}
                            >
                                <div className="text-center">
                                    <div className="text-lg font-medium text-gray-900">
                                        {loading.clearCache ? 'クリア中...' : 'キャッシュクリア'}
                                    </div>
                                    <div className="text-sm text-gray-500">アプリケーションキャッシュをクリア</div>
                                </div>
                            </button>
                            
                            <button 
                                onClick={handleReloadConfig}
                                disabled={loading.reloadConfig}
                                className={`p-4 border border-gray-300 rounded-lg transition-colors ${
                                    loading.reloadConfig 
                                        ? 'bg-gray-100 cursor-not-allowed' 
                                        : 'hover:bg-yellow-50 hover:border-yellow-300'
                                }`}
                            >
                                <div className="text-center">
                                    <div className="text-lg font-medium text-gray-900">
                                        {loading.reloadConfig ? 'リロード中...' : '設定リロード'}
                                    </div>
                                    <div className="text-sm text-gray-500">設定ファイルを再読み込み</div>
                                </div>
                            </button>
                            
                            <button 
                                onClick={handleViewLogs}
                                disabled={loading.viewLogs}
                                className={`p-4 border border-gray-300 rounded-lg transition-colors ${
                                    loading.viewLogs 
                                        ? 'bg-gray-100 cursor-not-allowed' 
                                        : 'hover:bg-purple-50 hover:border-purple-300'
                                }`}
                            >
                                <div className="text-center">
                                    <div className="text-lg font-medium text-gray-900">
                                        {loading.viewLogs ? '読み込み中...' : 'ログ確認'}
                                    </div>
                                    <div className="text-sm text-gray-500">アプリケーションログを表示</div>
                                </div>
                            </button>

                            <button 
                                onClick={handleCleanupStorage}
                                disabled={loading.cleanupStorage}
                                className={`p-4 border border-gray-300 rounded-lg transition-colors ${
                                    loading.cleanupStorage 
                                        ? 'bg-gray-100 cursor-not-allowed' 
                                        : 'hover:bg-red-50 hover:border-red-300'
                                }`}
                            >
                                <div className="text-center">
                                    <div className="text-lg font-medium text-gray-900">
                                        {loading.cleanupStorage ? 'クリーンアップ中...' : 'ストレージクリーン'}
                                    </div>
                                    <div className="text-sm text-gray-500">30日以上古いファイルを削除</div>
                                </div>
                            </button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* ファイルアップロードモーダル */}
            {showUploadModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 w-full max-w-2xl">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">DMARCレポートファイルアップロード</h2>
                            <button 
                                onClick={() => {
                                    setShowUploadModal(false);
                                    clearFiles();
                                }}
                                className="text-gray-500 hover:text-gray-700"
                            >
                                ✕
                            </button>
                        </div>
                        
                        {/* ドラッグ&ドロップエリア */}
                        <div 
                            className={`border-2 border-dashed rounded-lg p-8 text-center mb-4 transition-colors ${
                                dragActive ? 'border-blue-400 bg-blue-50' : 'border-gray-300'
                            }`}
                            onDragEnter={handleDrag}
                            onDragLeave={handleDrag}
                            onDragOver={handleDrag}
                            onDrop={handleDrop}
                        >
                            <div className="text-gray-600 mb-4">
                                <div className="text-lg font-medium mb-2">ファイルをドラッグ&ドロップ</div>
                                <div className="text-sm">または</div>
                            </div>
                            <button 
                                onClick={() => fileInputRef.current?.click()}
                                className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                            >
                                ファイルを選択
                            </button>
                            <input 
                                ref={fileInputRef}
                                type="file" 
                                multiple 
                                accept=".xml"
                                onChange={handleFileSelect}
                                className="hidden"
                            />
                        </div>
                        
                        {/* 選択されたファイル一覧 */}
                        {selectedFiles.length > 0 && (
                            <div className="mb-4">
                                <div className="flex justify-between items-center mb-2">
                                    <h3 className="font-medium">選択されたファイル ({selectedFiles.length})</h3>
                                    <button 
                                        onClick={clearFiles}
                                        className="text-red-500 text-sm hover:text-red-700"
                                    >
                                        すべて削除
                                    </button>
                                </div>
                                <div className="max-h-40 overflow-y-auto">
                                    {selectedFiles.map((file, index) => (
                                        <div key={index} className="flex justify-between items-center p-2 bg-gray-50 rounded mb-1">
                                            <span className="text-sm">{file.name} ({(file.size / 1024).toFixed(1)} KB)</span>
                                            <button 
                                                onClick={() => removeFile(index)}
                                                className="text-red-500 hover:text-red-700"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                        
                        {/* アクションボタン */}
                        <div className="flex justify-end gap-2">
                            <button 
                                onClick={() => {
                                    setShowUploadModal(false);
                                    clearFiles();
                                }}
                                className="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50"
                            >
                                キャンセル
                            </button>
                            <button 
                                onClick={handleUploadReports}
                                disabled={selectedFiles.length === 0 || loading.uploadReports}
                                className={`px-4 py-2 rounded ${
                                    selectedFiles.length === 0 || loading.uploadReports
                                        ? 'bg-gray-300 cursor-not-allowed'
                                        : 'bg-blue-500 text-white hover:bg-blue-600'
                                }`}
                            >
                                {loading.uploadReports ? 'アップロード中...' : 'アップロード'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* ログ確認モーダル */}
            {showLogModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 w-full max-w-6xl h-5/6 flex flex-col">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">アプリケーションログ</h2>
                            <button 
                                onClick={() => setShowLogModal(false)}
                                className="text-gray-500 hover:text-gray-700"
                            >
                                ✕
                            </button>
                        </div>
                        
                        {/* ログファイル選択とコントロール */}
                        <div className="flex gap-4 mb-4">
                            <select 
                                value={selectedLogFile}
                                onChange={(e) => loadLogFile(e.target.value)}
                                className="border border-gray-300 rounded px-3 py-2"
                            >
                                {logFiles.map((file) => (
                                    <option key={file.name} value={file.name}>
                                        {file.name} ({file.size})
                                    </option>
                                ))}
                            </select>
                            
                            <select 
                                value={logLines}
                                onChange={(e) => {
                                    setLogLines(parseInt(e.target.value));
                                    loadLogFile(selectedLogFile);
                                }}
                                className="border border-gray-300 rounded px-3 py-2"
                            >
                                <option value={50}>最新50行</option>
                                <option value={100}>最新100行</option>
                                <option value={200}>最新200行</option>
                                <option value={500}>最新500行</option>
                            </select>
                            
                            <button 
                                onClick={refreshLogs}
                                className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                            >
                                更新
                            </button>
                        </div>
                        
                        {/* ログファイル情報 */}
                        {logData && (
                            <div className="text-sm text-gray-600 mb-2">
                                ファイル: {logData.file_info.name} | 
                                サイズ: {logData.file_info.size} | 
                                最終更新: {logData.file_info.modified} | 
                                表示行数: {logData.file_info.displayed_lines}/{logData.file_info.total_lines}
                            </div>
                        )}
                        
                        {/* ログ内容 */}
                        <div className="flex-1 overflow-auto">
                            <pre className="bg-gray-100 p-4 rounded text-sm font-mono whitespace-pre-wrap overflow-x-auto">
                                {logData?.content || 'ログを読み込み中...'}
                            </pre>
                        </div>
                    </div>
                </div>
            )}
        </Layout>
    );
} 