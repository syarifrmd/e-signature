import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import certificatesRoute from '@/routes/certificates';

interface Props {
    certificate: {
        id: number;
        certificate_number: string;
        recipient_name: string;
        rank: string;
        generated_file_path: string;
        signer_name: string;
        verification_token: string;
        verified_at: string;
    };
    file_url: string | null;
}

export default function ShowCertificate({ certificate, file_url }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Certificates',
                    href: certificatesRoute.index().url,
                },
                {
                    title: certificate.certificate_number,
                    href: certificatesRoute.show(certificate.id).url,
                },
            ]}
        >
            <Head title={`Certificate - ${certificate.recipient_name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <div className="flex justify-between items-center mb-6">
                                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Certificate for {certificate.recipient_name} (Juara {certificate.rank})
                                </h2>
                                <div className="flex gap-2">
                                    <a href={certificatesRoute.download(certificate.id).url} target="_blank" rel="noopener noreferrer">
                                        <Button>Download Certificate</Button>
                                    </a>
                                    <a href={certificatesRoute.create().url}>
                                        <Button variant="outline">Create Another</Button>
                                    </a>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                                <div className="lg:col-span-2 border rounded-lg p-4 bg-gray-50 dark:bg-gray-900 flex justify-center">
                                    {file_url ? (
                                        <img 
                                            src={file_url} 
                                            alt="Generated Certificate" 
                                            className="max-w-full h-auto shadow-lg"
                                        />
                                    ) : (
                                        <p>Certificate file not found.</p>
                                    )}
                                </div>

                                <div className="space-y-4">
                                    <div className="bg-blue-50 dark:bg-blue-950 rounded-lg p-4">
                                        <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                            Digital Signature Info
                                        </h3>
                                        <div className="space-y-2">
                                            <div>
                                                <p className="text-xs text-gray-600 dark:text-gray-400">Ditandatangani oleh</p>
                                                <p className="font-semibold text-gray-900 dark:text-gray-100">{certificate.signer_name}</p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-gray-600 dark:text-gray-400">Status</p>
                                                <p className="text-green-600 dark:text-green-400 font-medium">✓ Terverifikasi</p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-gray-600 dark:text-gray-400">Waktu Verifikasi</p>
                                                <p className="text-sm text-gray-900 dark:text-gray-100">{certificate.verified_at}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="bg-gray-100 dark:bg-gray-900 rounded-lg p-4">
                                        <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            Verification Token
                                        </h3>
                                        <p className="text-xs font-mono bg-white dark:bg-gray-800 p-2 rounded break-all text-gray-800 dark:text-gray-200">
                                            {certificate.verification_token}
                                        </p>
                                        <p className="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                            Scan QR code pada sertifikat untuk verifikasi otomatis
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
