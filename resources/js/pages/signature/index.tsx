import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState, useRef, useEffect } from 'react';
import * as pdfjsLib from 'pdfjs-dist';

// Configure PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = `//unpkg.com/pdfjs-dist@${pdfjsLib.version}/build/pdf.worker.min.mjs`;

export default function CreateSignature() {
    const { data, setData, post, processing, errors } = useForm<{ 
        file: File | null;
        letter_number: string;
        letter_date: string;
        signer_name: string;
        letter_subject: string;
        qr_position: string;
        qr_x: number | null;
        qr_y: number | null;
        qr_page: number;
        preview_width: number | null;
        preview_height: number | null;
    }>({
        file: null,
        letter_number: '',
        letter_date: '',
        signer_name: '',
        letter_subject: '',
        qr_position: 'bottom-right',
        qr_x: null,
        qr_y: null,
        qr_page: 1,
        preview_width: null,
        preview_height: null,
    });

    const [pdfFile, setPdfFile] = useState<File | null>(null);
    const [numPages, setNumPages] = useState<number>(0);
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [qrPosition, setQrPosition] = useState<{ x: number; y: number } | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [dragOffset, setDragOffset] = useState({ x: 0, y: 0 });
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files ? e.target.files[0] : null;
        if (file) {
            setData('file', file);
            setPdfFile(file);
        }
    };

    // Render PDF to canvas using PDF.js
    useEffect(() => {
        if (!pdfFile || !canvasRef.current) return;

        const loadPdf = async () => {
            const fileReader = new FileReader();
            fileReader.onload = async function() {
                const typedArray = new Uint8Array(this.result as ArrayBuffer);
                const pdf = await pdfjsLib.getDocument({ data: typedArray }).promise;
                setNumPages(pdf.numPages);
                
                // Render current page
                const page = await pdf.getPage(currentPage);
                const canvas = canvasRef.current!;
                const context = canvas.getContext('2d')!;
                
                // Set viewport to scale
                const viewport = page.getViewport({ scale: 1.5 });
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                canvas.style.width = `${viewport.width}px`;
                canvas.style.height = `${viewport.height}px`;
                
                // Store actual canvas dimensions for precise scaling
                setData(data => ({
                    ...data,
                    preview_width: viewport.width,
                    preview_height: viewport.height
                }));
                
                await page.render({
                    canvasContext: context,
                    viewport: viewport,
                    intent: 'display'
                } as any).promise;
            };
            fileReader.readAsArrayBuffer(pdfFile);
        };

        loadPdf();
    }, [pdfFile, currentPage]);

    const handleCanvasClick = (e: React.MouseEvent<HTMLDivElement>) => {
        if (data.qr_position !== 'manual' || !canvasRef.current) return;
        
        const rect = canvasRef.current.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Calculate percentage position relative to canvas size
            const percentX = x / rect.width;
            const percentY = y / rect.height;
        
        setQrPosition({ x, y });
        setData(data => ({
            ...data,
            qr_x: percentX,
            qr_y: percentY,
            preview_width: null,
            preview_height: null
        }));
    };

    const handleQrMouseDown = (e: React.MouseEvent) => {
        if (data.qr_position !== 'manual') return;
        e.stopPropagation();
        setIsDragging(true);
        
        if (qrPosition) {
            setDragOffset({
                x: e.clientX - qrPosition.x,
                y: e.clientY - qrPosition.y
            });
        }
    };

    const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
        if (!isDragging || !canvasRef.current) return;
        
        const rect = canvasRef.current.getBoundingClientRect();
        const x = e.clientX - rect.left - dragOffset.x;
        const y = e.clientY - rect.top - dragOffset.y;
        
        // Calculate percentage position relative to canvas size
            const percentX = x / rect.width;
            const percentY = y / rect.height;
        
        setQrPosition({ x, y });
        setData(data => ({
            ...data,
            qr_x: percentX,
            qr_y: percentY,
            preview_width: null,
            preview_height: null
        }));
    };

    const handleMouseUp = () => {
        setIsDragging(false);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setData('qr_page', currentPage);
        post('/signatures');
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Create Signature', href: '/signatures/create' }]}>
            <Head title="Create Signature" />
            <div className="p-6">
                <div className="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <h2 className="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Buat QR Code Tanda Tangan Digital</h2>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Upload Document (PDF)
                            </label>
                            <input
                                type="file"
                                accept=".pdf"
                                className="w-full p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                onChange={handleFileChange}
                            />
                            {errors.file && <div className="text-red-500 text-sm mt-1">{errors.file}</div>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                No. Surat
                            </label>
                            <input
                                type="text"
                                className="w-full p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                value={data.letter_number}
                                onChange={(e) => setData('letter_number', e.target.value)}
                                placeholder="Contoh: 001/SKT/XII/2025"
                            />
                            {errors.letter_number && <div className="text-red-500 text-sm mt-1">{errors.letter_number}</div>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tanggal Surat
                            </label>
                            <input
                                type="date"
                                className="w-full p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                value={data.letter_date}
                                onChange={(e) => setData('letter_date', e.target.value)}
                            />
                            {errors.letter_date && <div className="text-red-500 text-sm mt-1">{errors.letter_date}</div>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Penandatangan
                            </label>
                            <input
                                type="text"
                                className="w-full p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                value={data.signer_name}
                                onChange={(e) => setData('signer_name', e.target.value)}
                                placeholder="Contoh: H. Albaarri Ahmad Sobari"
                            />
                            {errors.signer_name && <div className="text-red-500 text-sm mt-1">{errors.signer_name}</div>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Perihal
                            </label>
                            <textarea
                                className="w-full p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                rows={3}
                                value={data.letter_subject}
                                onChange={(e) => setData('letter_subject', e.target.value)}
                                placeholder="Contoh: Surat Keterangan Kerja"
                            />
                            {errors.letter_subject && <div className="text-red-500 text-sm mt-1">{errors.letter_subject}</div>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Posisi QR Code
                            </label>
                            <select
                                className="w-full p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                value={data.qr_position}
                                onChange={(e) => {
                                    setData('qr_position', e.target.value);
                                    if (e.target.value !== 'manual') {
                                        setQrPosition(null);
                                        setData('qr_x', null);
                                        setData('qr_y', null);
                                    }
                                }}
                            >
                                <option value="bottom-right">Kanan Bawah</option>
                                <option value="bottom-left">Kiri Bawah</option>
                                <option value="top-right">Kanan Atas</option>
                                <option value="top-left">Kiri Atas</option>
                                <option value="manual">Posisi Manual (Klik di Preview)</option>
                            </select>
                            {errors.qr_position && <div className="text-red-500 text-sm mt-1">{errors.qr_position}</div>}
                        </div>

                        {pdfFile && (
                            <div className="border rounded-md p-4 bg-gray-50 dark:bg-gray-900">
                                <div className="flex justify-between items-center mb-2">
                                    <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Preview Dokumen {data.qr_position === 'manual' && '(Klik/Drag untuk posisikan QR)'}
                                    </h3>
                                    {numPages > 1 && (
                                        <div className="flex gap-2 items-center">
                                            <button
                                                type="button"
                                                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                                                disabled={currentPage === 1}
                                                className="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded disabled:opacity-50"
                                            >
                                                ←
                                            </button>
                                            <span className="text-sm">
                                                Hal {currentPage} / {numPages}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() => setCurrentPage(Math.min(numPages, currentPage + 1))}
                                                disabled={currentPage === numPages}
                                                className="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded disabled:opacity-50"
                                            >
                                                →
                                            </button>
                                        </div>
                                    )}
                                </div>
                                <div 
                                    ref={containerRef}
                                    className="relative inline-block border bg-white cursor-crosshair"
                                    onClick={handleCanvasClick}
                                    onMouseMove={handleMouseMove}
                                    onMouseUp={handleMouseUp}
                                    onMouseLeave={handleMouseUp}
                                >
                                    <canvas ref={canvasRef} />
                                    
                                    {qrPosition && data.qr_position === 'manual' && (
                                        <div
                                            className="absolute w-24 h-24 border-2 border-dashed border-blue-500 bg-blue-100 bg-opacity-50 flex items-center justify-center cursor-move"
                                            style={{
                                                left: `${qrPosition.x}px`,
                                                top: `${qrPosition.y}px`,
                                                transform: 'translate(-50%, -50%)'
                                            }}
                                            onMouseDown={handleQrMouseDown}
                                        >
                                            <span className="text-xs font-bold text-blue-600">QR Code</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={processing || !data.file}
                            className="w-full bg-gradient-to-r from-pink-500 to-pink-600 text-white px-6 py-3 rounded-md font-semibold hover:from-pink-600 hover:to-pink-700 disabled:opacity-50 transition-all"
                        >
                            {processing ? 'Generating...' : 'Generate'}
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
