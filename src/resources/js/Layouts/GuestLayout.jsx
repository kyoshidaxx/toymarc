import ApplicationLogo from '@/components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/" className="flex items-center space-x-3">
                    <ApplicationLogo className="h-16 w-16 fill-current text-blue-600" />
                    <div className="text-center">
                        <h1 className="text-2xl font-bold text-gray-900 hidden sm:block">toymarc</h1>
                        <h1 className="text-xl font-bold text-gray-900 sm:hidden">toymarc</h1>
                    </div>
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {children}
            </div>
        </div>
    );
}
