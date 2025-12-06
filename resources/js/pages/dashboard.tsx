import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

type DashboardProps = {
    summary: {
        signatures: number;
        certificates: number;
    };
    recent: {
        signatures: { id: number; file_name: string; created_at: string }[];
        certificates: { id: number; recipient_name: string; certificate_number: string; created_at: string }[];
    };
};

export default function Dashboard({ summary, recent }: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {/* Summary cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="rounded-xl border bg-white dark:bg-gray-800 p-6">
                        <div className="text-sm text-gray-600 dark:text-gray-400">E‑Sign Documents</div>
                        <div className="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{summary.signatures}</div>
                        <div className="mt-1 text-xs text-gray-500 dark:text-gray-400">Total dokumen ditandatangani</div>
                    </div>
                    <div className="rounded-xl border bg-white dark:bg-gray-800 p-6">
                        <div className="text-sm text-gray-600 dark:text-gray-400">Certificates</div>
                        <div className="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{summary.certificates}</div>
                        <div className="mt-1 text-xs text-gray-500 dark:text-gray-400">Total sertifikat dibuat</div>
                    </div>
                </div>

                {/* Recent activity */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border bg-white dark:bg-gray-800">
                        <div className="border-b p-4 text-sm font-semibold text-gray-900 dark:text-gray-100">Aktivitas terbaru — E‑Sign</div>
                        <ul className="divide-y">
                            {recent.signatures.map((s) => (
                                <li key={s.id} className="p-4 text-sm flex items-center justify-between">
                                    <span className="text-gray-900 dark:text-gray-100 truncate">{s.file_name}</span>
                                    <span className="text-gray-500 dark:text-gray-400">{new Date(s.created_at).toLocaleString()}</span>
                                </li>
                            ))}
                            {recent.signatures.length === 0 && (
                                <li className="p-4 text-sm text-gray-500 dark:text-gray-400">Belum ada dokumen</li>
                            )}
                        </ul>
                    </div>
                    <div className="rounded-xl border bg-white dark:bg-gray-800">
                        <div className="border-b p-4 text-sm font-semibold text-gray-900 dark:text-gray-100">Aktivitas terbaru — Certificates</div>
                        <ul className="divide-y">
                            {recent.certificates.map((c) => (
                                <li key={c.id} className="p-4 text-sm flex items-center justify-between">
                                    <span className="text-gray-900 dark:text-gray-100 truncate">{c.recipient_name} • {c.certificate_number}</span>
                                    <span className="text-gray-500 dark:text-gray-400">{new Date(c.created_at).toLocaleString()}</span>
                                </li>
                            ))}
                            {recent.certificates.length === 0 && (
                                <li className="p-4 text-sm text-gray-500 dark:text-gray-400">Belum ada sertifikat</li>
                            )}
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
