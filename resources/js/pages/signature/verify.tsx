import AppLayout from '@/layouts/app-layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type VerificationResult = {
    valid: boolean;
    reason?: string;
    document?: {
        letter_number?: string | null;
        letter_date?: string | null;
        letter_subject?: string | null;
        signer_name?: string | null;
        signed_at?: string | null;
    };
};

export default function VerifySignature() {
    const { data, setData, post, processing, errors } = useForm<{ file: File | null }>({
        file: null,
    });

    const { verification_result } = usePage<{ verification_result?: VerificationResult }>().props;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/verify');
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Verify Signature', href: '/verify' }]}>
            <Head title="Verify Signature" />
            <div className="p-6">
                <div className="max-w-2xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <h2 className="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Verify Signature</h2>
                    
                    <form onSubmit={submit}>
                        <div className="mb-4">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Upload Dokumen yang Sudah Ditandatangani
                            </label>
                            <input
                                type="file"
                                accept=".pdf"
                                className="w-full p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                onChange={(e) => setData('file', e.target.files ? e.target.files[0] : null)}
                            />
                            {errors.file && <div className="text-red-500 text-sm mt-1">{errors.file}</div>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing || !data.file}
                            className="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 disabled:opacity-50"
                        >
                            {processing ? 'Memeriksa...' : 'Verifikasi Dokumen'}
                        </button>
                    </form>

                    {verification_result && (
                        <div className={`mt-6 p-4 rounded-md border ${verification_result.valid ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'}`}>
                            <h3 className="font-bold text-lg">{verification_result.valid ? 'Dokumen Otentik' : 'Dokumen Tidak Valid'}</h3>
                            {!verification_result.valid && (
                                <p className="mt-2 text-sm">{verification_result.reason}</p>
                            )}
                            {verification_result.valid && verification_result.document && (
                                <div className="mt-4 grid grid-cols-1 gap-2 text-sm">
                                    <div>
                                        <span className="font-semibold">No. Surat:</span> {verification_result.document.letter_number || '-'}
                                    </div>
                                    <div>
                                        <span className="font-semibold">Tanggal:</span> {verification_result.document.letter_date || '-'}
                                    </div>
                                    <div>
                                        <span className="font-semibold">Perihal:</span> {verification_result.document.letter_subject || '-'}
                                    </div>
                                    <div>
                                        <span className="font-semibold">Penandatangan:</span> {verification_result.document.signer_name || '-'}
                                    </div>
                                    <div>
                                        <span className="font-semibold">Ditandatangani Pada:</span> {verification_result.document.signed_at || '-'}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
