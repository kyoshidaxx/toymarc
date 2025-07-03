import React from 'react';
import Navigation from './Navigation';

export default function Layout({ children }) {
    return (
        <div className="min-h-screen bg-gray-50">
            <Navigation />
            <main>
                {children}
            </main>
        </div>
    );
} 