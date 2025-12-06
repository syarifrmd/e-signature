import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Props {
    signature: any;
    qrSvg: string;
    qrData: string;
    file_url?: string;
    signed_file_url?: string;
}

export default function ShowSignature({ signature, qrSvg, qrData, file_url, signed_file_url }: Props) {
    return (
        <AppLayout breadcrumbs={[
            { title: 'Create Signature', href: '/signatures/create' },
            { title: 'Signature Details', href: '#' }
        ]}>
            <Head title="Signature Details" />
            <div className="p-6">
                <div className="max-w-4xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <h2 className="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Signature Generated</h2>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 className="text-lg font-medium mb-2 text-gray-700 dark:text-gray-300">QR Code</h3>
                            <div className="bg-white p-4 rounded border inline-block" dangerouslySetInnerHTML={{ __html: qrSvg }} />
                            <p className="text-sm text-gray-500 mt-2">Scan this QR code to verify the signature.</p>
                            
                            <div className="mt-4 flex flex-col gap-2">
                                {signed_file_url && (
                                    <a 
                                        href={signed_file_url} 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Download Signed Document (PDF)
                                    </a>
                                )}
                                
                                {file_url && (
                                    <a 
                                        href={file_url} 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="inline-flex justify-center items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                    >
                                        Download Original Document
                                    </a>
                                )}
                            </div>
                        </div>
                        
                        <div className="space-y-4">
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">File Name</h3>
                                <p className="font-medium dark:text-gray-200">{signature.file_name || 'N/A'}</p>
                            </div>
                            
                            {signature.letter_number && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">No. Surat</h3>
                                    <p className="dark:text-gray-200">{signature.letter_number}</p>
                                </div>
                            )}
                            
                            {signature.letter_date && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Surat</h3>
                                    <p className="dark:text-gray-200">{new Date(signature.letter_date).toLocaleDateString('id-ID')}</p>
                                </div>
                            )}
                            
                            {signature.signer_name && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Penandatangan</h3>
                                    <p className="dark:text-gray-200">{signature.signer_name}</p>
                                </div>
                            )}
                            
                            {signature.letter_subject && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Perihal</h3>
                                    <p className="dark:text-gray-200">{signature.letter_subject}</p>
                                </div>
                            )}
                            
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Content Hash</h3>
                                <p className="font-mono text-sm break-all dark:text-gray-200">{signature.content_hash}</p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Signature</h3>
                                <p className="font-mono text-xs break-all dark:text-gray-200 bg-gray-50 dark:bg-gray-900 p-2 rounded max-h-32 overflow-y-auto">
                                    {signature.signature}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Signed At</h3>
                                <p className="dark:text-gray-200">{new Date(signature.signed_at).toLocaleString()}</p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Algorithm</h3>
                                <p className="dark:text-gray-200">{signature.alg}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
