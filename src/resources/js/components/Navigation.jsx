import { Link, usePage } from '@inertiajs/react';
import React from 'react';

export default function Navigation() {
    const { url } = usePage();
    
    const navigation = [
        { name: 'Dashboard', href: '/dashboard', icon: '📊' },
        { name: 'DMARC Reports', href: '/dmarc-reports', icon: '📋' },
        { name: 'Analytics', href: '/analytics', icon: '📈' },
        { name: 'Settings', href: '/settings', icon: '⚙️' },
    ];

    return (
        <nav className="bg-white shadow-sm border-b">
            <div className="container mx-auto px-4">
                <div className="flex justify-between items-center h-16">
                    {/* ロゴ */}
                    <div className="flex items-center">
                        <Link href="/dashboard" className="flex items-center space-x-2">
                            <span className="text-2xl">🛡️</span>
                            <span className="text-xl font-bold text-gray-900">DMARC Reports</span>
                        </Link>
                    </div>

                    {/* ナビゲーションリンク */}
                    <div className="hidden md:flex space-x-8">
                        {navigation.map((item) => {
                            const isActive = url === item.href;
                            return (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                                        isActive
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                    }`}
                                >
                                    <span>{item.icon}</span>
                                    <span>{item.name}</span>
                                </Link>
                            );
                        })}
                    </div>

                    {/* モバイルメニューボタン */}
                    <div className="md:hidden">
                        <button className="text-gray-600 hover:text-gray-900">
                            <span className="text-2xl">☰</span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>
    );
} 