import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { CheckCircle, XCircle, Award } from 'lucide-react';

interface VerifyProps {
    verified: boolean;
    message?: string;
    certificate?: {
        certificate_number: string;
        recipient_name: string;
        rank: string;
        signer_name: string;
        verified_at: string;
        created_at: string;
        file_url: string;
    };
}

export default function VerifyCertificate({ verified, message, certificate }: VerifyProps) {
    const getRankBadge = (rank: string) => {
        const badges: Record<string, { label: string; color: string }> = {
            '1': { label: 'Juara 1', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' },
            '2': { label: 'Juara 2', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' },
            '3': { label: 'Juara 3', color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' },
        };
        return badges[rank] || badges['1'];
    };

    return (
        <AppLayout>
            <Head title="Certificate Verification" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {verified && certificate ? (
                                <>
                                    <div className="flex items-center justify-center mb-6">
                                        <CheckCircle className="w-16 h-16 text-green-500" />
                                    </div>
                                    
                                    <h2 className="text-2xl font-bold text-center text-gray-900 dark:text-gray-100 mb-2">
                                        Sertifikat Valid
                                    </h2>
                                    <p className="text-center text-gray-600 dark:text-gray-400 mb-8">
                                        Sertifikat telah diverifikasi dan sah
                                    </p>

                                    <div className="border-t border-b border-gray-200 dark:border-gray-700 py-6 space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">Nomor Sertifikat</p>
                                                <p className="font-mono font-semibold text-gray-900 dark:text-gray-100">
                                                    {certificate.certificate_number}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">Tanggal Dibuat</p>
                                                <p className="font-semibold text-gray-900 dark:text-gray-100">
                                                    {certificate.created_at}
                                                </p>
                                            </div>
                                        </div>

                                        <div>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Penerima</p>
                                            <p className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                                {certificate.recipient_name}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">Prestasi</p>
                                            <span className={`inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold ${getRankBadge(certificate.rank).color}`}>
                                                <Award className="w-4 h-4" />
                                                {getRankBadge(certificate.rank).label}
                                            </span>
                                        </div>

                                        <div className="bg-blue-50 dark:bg-blue-950 rounded-lg p-4">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Ditandatangani Oleh</p>
                                            <p className="font-semibold text-gray-900 dark:text-gray-100">
                                                {certificate.signer_name}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Diverifikasi pada: {certificate.verified_at}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-6">
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">Preview Sertifikat:</p>
                                        <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                            <img 
                                                src={certificate.file_url} 
                                                alt="Certificate Preview"
                                                className="w-full h-auto"
                                            />
                                        </div>
                                    </div>

                                    <div className="mt-6 flex justify-center">
                                        <a
                                            href={certificate.file_url}
                                            download
                                            className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors"
                                        >
                                            Download Sertifikat
                                        </a>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div className="flex items-center justify-center mb-6">
                                        <XCircle className="w-16 h-16 text-red-500" />
                                    </div>
                                    
                                    <h2 className="text-2xl font-bold text-center text-gray-900 dark:text-gray-100 mb-2">
                                        Verifikasi Gagal
                                    </h2>
                                    <p className="text-center text-gray-600 dark:text-gray-400 mb-8">
                                        {message || 'Sertifikat tidak dapat diverifikasi'}
                                    </p>

                                    <div className="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                        <p className="text-sm text-red-800 dark:text-red-200">
                                            Token verifikasi tidak valid atau sertifikat sudah tidak berlaku. 
                                            Pastikan Anda menggunakan link verifikasi yang benar dari QR code pada sertifikat asli.
                                        </p>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
