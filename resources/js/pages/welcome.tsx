import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { PenTool, CheckCircle, Award, Shield } from 'lucide-react';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="E-Signature - Digital Document Signing" />
            <div className="min-h-screen bg-zinc-900">
                <header className="flex w-full justify-center pt-8">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-block rounded-lg border-2 border-yellow-400 bg-yellow-400 px-6 py-2 text-sm font-medium text-yellow-900 transition-all hover:bg-yellow-300 hover:border-yellow-300"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-block rounded-lg px-6 py-2 text-sm font-medium text-yellow-100 transition-all hover:text-white"
                                >
                                    Masuk
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={register()}
                                        className="inline-block rounded-lg border-2 border-yellow-400 px-6 py-2 text-sm font-medium text-yellow-100 transition-all hover:bg-yellow-400 hover:text-yellow-900"
                                    >
                                        Daftar
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>
                </header>
                
                {/* Hero Section */}
                <div className="flex w-full items-center justify-center py-16 lg:py-24">
                    <main className="w-full max-w-6xl px-6">
                        {/* Logo and Heading */}
                        <div className="mb-16 text-center">
                            <div className="mb-8 flex justify-center">
                                <img 
                                    src="/storage/logo/logo.png" 
                                    alt="E-Signature Logo" 
                                    className="h-32 w-auto"
                                    onError={(e) => {
                                        e.currentTarget.src = "/logo/logo.png";
                                    }}
                                />
                            </div>
                            <h1 className="mb-4 text-5xl font-bold text-white lg:text-6xl">
                                E-Signature
                            </h1>
                            <p className="text-xl text-yellow-100 lg:text-2xl">
                                Sistem Tanda Tangan Digital & Pembuatan Sertifikat
                            </p>
                            <p className="mt-4 text-lg text-yellow-200">
                                Aman, cepat, dan dapat diverifikasi
                            </p>
                        </div>

                        {/* Features Grid */}
                        <div className="mb-16 grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                            <div className="rounded-lg bg-yellow-800/50 p-6 backdrop-blur-sm transition-transform hover:scale-105">
                                <div className="mb-4 flex justify-center">
                                    <PenTool className="h-12 w-12 text-yellow-300" />
                                </div>
                                <h3 className="mb-2 text-center text-lg font-semibold text-white">
                                    Tanda Tangan Dokumen
                                </h3>
                                <p className="text-center text-sm text-yellow-200">
                                    Tanda tangani dokumen secara digital dengan aman
                                </p>
                            </div>

                            <div className="rounded-lg bg-yellow-800/50 p-6 backdrop-blur-sm transition-transform hover:scale-105">
                                <div className="mb-4 flex justify-center">
                                    <CheckCircle className="h-12 w-12 text-yellow-300" />
                                </div>
                                <h3 className="mb-2 text-center text-lg font-semibold text-white">
                                    Verifikasi Keaslian
                                </h3>
                                <p className="text-center text-sm text-yellow-200">
                                    Verifikasi keaslian dokumen yang telah ditandatangani
                                </p>
                            </div>

                            <div className="rounded-lg bg-yellow-800/50 p-6 backdrop-blur-sm transition-transform hover:scale-105">
                                <div className="mb-4 flex justify-center">
                                    <Award className="h-12 w-12 text-yellow-300" />
                                </div>
                                <h3 className="mb-2 text-center text-lg font-semibold text-white">
                                    Buat Sertifikat
                                </h3>
                                <p className="text-center text-sm text-yellow-200">
                                    Buat sertifikat profesional dengan tanda tangan digital
                                </p>
                            </div>

                            <div className="rounded-lg bg-yellow-800/50 p-6 backdrop-blur-sm transition-transform hover:scale-105">
                                <div className="mb-4 flex justify-center">
                                    <Shield className="h-12 w-12 text-yellow-300" />
                                </div>
                                <h3 className="mb-2 text-center text-lg font-semibold text-white">
                                    Keamanan Terjamin
                                </h3>
                                <p className="text-center text-sm text-yellow-200">
                                    Dilengkapi QR Code dan token verifikasi unik
                                </p>
                            </div>
                        </div>

                    </main>
                </div>
            </div>
        </>
    );
}
