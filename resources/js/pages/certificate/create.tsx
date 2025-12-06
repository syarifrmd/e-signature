import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import certificatesRoute from '@/routes/certificates';

export default function CreateCertificate() {
    const { data, setData, post, processing, errors } = useForm<{
        recipient_name: string;
        rank: string;
    }>({
        recipient_name: '',
        rank: '1',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(certificatesRoute.store().url);
    };

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Create Certificate',
                    href: certificatesRoute.create().url,
                },
            ]}
        >
            <Head title="Create Certificate" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Generate New Certificate</h2>
                            
                            <form onSubmit={submit} className="space-y-6 max-w-xl">
                                <div className="grid w-full max-w-sm items-center gap-1.5">
                                    <Label htmlFor="recipient_name">Recipient Name (Nama Penerima)</Label>
                                    <Input
                                        id="recipient_name"
                                        type="text"
                                        value={data.recipient_name}
                                        onChange={(e) => setData('recipient_name', e.target.value)}
                                        required
                                        autoFocus
                                    />
                                    <InputError message={errors.recipient_name} className="mt-2" />
                                </div>

                                <div className="grid w-full max-w-sm items-center gap-1.5">
                                    <Label htmlFor="rank">Rank (Juara)</Label>
                                    <select
                                        id="rank"
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        value={data.rank}
                                        onChange={(e) => setData('rank', e.target.value)}
                                        required
                                    >
                                        <option value="1">Juara 1</option>
                                        <option value="2">Juara 2</option>
                                        <option value="3">Juara 3</option>
                                    </select>
                                    <InputError message={errors.rank} className="mt-2" />
                                </div>

                                <div className="rounded-md bg-blue-50 dark:bg-blue-950 p-4">
                                    <div className="flex">
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-blue-800 dark:text-blue-200">
                                                Digital Signature
                                            </h3>
                                            <div className="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                                <p>Sertifikat akan ditandatangani secara digital menggunakan akun Anda. QR code verifikasi akan ditambahkan secara otomatis untuk memastikan keaslian sertifikat.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        Generate Certificate
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
