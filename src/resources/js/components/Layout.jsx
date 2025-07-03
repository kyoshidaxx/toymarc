import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Layout({ children }) {
    const { url, auth } = usePage();
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    
    const navigation = [
        { name: 'Dashboard', href: '/dashboard', icon: '📊' },
        { name: 'DMARC Reports', href: '/dmarc-reports', icon: '📋' },
        { name: 'Analytics', href: '/analytics', icon: '📈' },
        { name: 'Settings', href: '/settings', icon: '⚙️' },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            {/* ナビゲーションバー */}
            <nav className="bg-white shadow-sm border-b">
                <div className="container mx-auto px-4">
                    <div className="flex justify-between items-center h-16">
                        {/* ロゴ */}
                        <div className="flex items-center min-w-0 flex-1">
                            <Link href="/dashboard" className="flex items-center space-x-3 min-w-0">
                                <svg
                                    className="w-8 h-8 text-blue-600 flex-shrink-0"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                >
                                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z" />
                                </svg>
                                <span className="text-xl font-bold text-gray-900 truncate hidden sm:block">DMARC Reports</span>
                                <span className="text-lg font-bold text-gray-900 truncate sm:hidden">DMARC</span>
                            </Link>
                        </div>

                        {/* デスクトップナビゲーション */}
                        <div className="hidden lg:flex items-center space-x-6">
                            {navigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                                        url === item.href
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                    }`}
                                >
                                    <span>{item.icon}</span>
                                    <span>{item.name}</span>
                                </Link>
                            ))}
                        </div>

                        {/* ユーザーメニュー */}
                        <div className="hidden md:flex items-center space-x-4 ml-4">
                            <div className="relative">
                                <button
                                    onClick={() => setShowingNavigationDropdown(!showingNavigationDropdown)}
                                    className="flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                                >
                                    <span className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                                        {auth.user.name.charAt(0).toUpperCase()}
                                    </span>
                                    <span className="hidden lg:block">{auth.user.name}</span>
                                    <svg
                                        className={`w-4 h-4 transition-transform flex-shrink-0 ${
                                            showingNavigationDropdown ? 'rotate-180' : ''
                                        }`}
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                {/* ドロップダウンメニュー */}
                                {showingNavigationDropdown && (
                                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                        <Link
                                            href="/profile"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            onClick={() => setShowingNavigationDropdown(false)}
                                        >
                                            プロフィール
                                        </Link>
                                        <Link
                                            href="/logout"
                                            method="post"
                                            as="button"
                                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            onClick={() => setShowingNavigationDropdown(false)}
                                        >
                                            ログアウト
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* モバイルメニューボタン */}
                        <div className="md:hidden ml-2">
                            <button
                                onClick={() => setShowingNavigationDropdown(!showingNavigationDropdown)}
                                className="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                            >
                                <svg
                                    className="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    {showingNavigationDropdown ? (
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    ) : (
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                                    )}
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* モバイルナビゲーション */}
                    {showingNavigationDropdown && (
                        <div className="md:hidden">
                            <div className="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                                {navigation.map((item) => (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className={`flex items-center space-x-2 px-3 py-2 rounded-md text-base font-medium transition-colors ${
                                            url === item.href
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                        }`}
                                        onClick={() => setShowingNavigationDropdown(false)}
                                    >
                                        <span>{item.icon}</span>
                                        <span>{item.name}</span>
                                    </Link>
                                ))}
                            </div>
                            
                            {/* モバイルユーザーメニュー */}
                            <div className="pt-4 pb-3 border-t border-gray-200">
                                <div className="px-4">
                                    <div className="flex items-center space-x-3">
                                        <span className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                            {auth.user.name.charAt(0).toUpperCase()}
                                        </span>
                                        <div>
                                            <div className="text-base font-medium text-gray-800">{auth.user.name}</div>
                                            <div className="text-sm font-medium text-gray-500">{auth.user.email}</div>
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 px-2 space-y-1">
                                    <Link
                                        href="/profile"
                                        className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100"
                                        onClick={() => setShowingNavigationDropdown(false)}
                                    >
                                        プロフィール
                                    </Link>
                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        className="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100"
                                        onClick={() => setShowingNavigationDropdown(false)}
                                    >
                                        ログアウト
                                    </Link>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </nav>

            {/* メインコンテンツ */}
            <main className="flex-1">
                {children}
            </main>
        </div>
    );
} 