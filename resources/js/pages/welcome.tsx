import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Calendar, CheckCircle2, Clock, FileText, Plus, Truck, User, XCircle } from 'lucide-react';

interface RefuelingRequest {
    id: number;
    no_do: string;
    nopol: string;
    distributor_name: string;
    status: 'pending' | 'approved' | 'rejected' | 'completed';
    created_at: string;
    creator: {
        name: string;
    };
    approver?: {
        name: string;
    };
    approved_at?: string;
    rejection_reason?: string;
}

interface PaginatedRequests {
    data: RefuelingRequest[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    requests?: PaginatedRequests;
    userRole?: 'distributor' | 'sales' | 'shift';
    canCreateRequest?: boolean;
    auth?: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
    [key: string]: unknown;
}

export default function Welcome({ requests, userRole, canCreateRequest = false, auth }: Props) {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [selectedRequestId, setSelectedRequestId] = useState<number | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        no_do: '',
        nopol: '',
        distributor_name: '',
    });

    const [rejectData, setRejectDataState] = useState({ rejection_reason: '' });
    const setRejectData = (field: string, value: string) => {
        setRejectDataState(prev => ({ ...prev, [field]: value }));
    };
    const resetReject = () => setRejectDataState({ rejection_reason: '' });

    const handleCreateRequest = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('refueling-requests.store'), {
            onSuccess: () => {
                reset();
                setShowCreateModal(false);
            },
        });
    };

    const handleApprove = (requestId: number) => {
        router.patch(route('refueling-requests.update', requestId), {
            action: 'approve'
        });
    };

    const handleReject = (requestId: number) => {
        setSelectedRequestId(requestId);
        setShowRejectModal(true);
    };

    const handleRejectSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedRequestId) {
            router.patch(route('refueling-requests.update', selectedRequestId), {
                action: 'reject',
                rejection_reason: rejectData.rejection_reason
            }, {
                onSuccess: () => {
                    resetReject();
                    setShowRejectModal(false);
                    setSelectedRequestId(null);
                },
            });
        }
    };

    const handleComplete = (requestId: number) => {
        router.patch(route('refueling-requests.update', requestId), {
            action: 'complete'
        });
    };

    const handleEdit = (requestId: number) => {
        router.get(route('refueling-requests.show', requestId));
    };

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            pending: { variant: 'secondary' as const, icon: Clock, text: 'Pending' },
            approved: { variant: 'default' as const, icon: CheckCircle2, text: 'Approved' },
            rejected: { variant: 'destructive' as const, icon: XCircle, text: 'Rejected' },
            completed: { variant: 'default' as const, icon: Truck, text: 'Completed' }
        };

        const config = statusConfig[status as keyof typeof statusConfig];
        const IconComponent = config.icon;

        return (
            <Badge variant={config.variant} className="flex items-center gap-1">
                <IconComponent className="w-3 h-3" />
                {config.text}
            </Badge>
        );
    };

    const getRoleDisplayName = (role?: string) => {
        const roleNames = {
            distributor: 'Distributor',
            sales: 'Sales Team',
            shift: 'Shift Operator'
        };
        return role ? roleNames[role as keyof typeof roleNames] : '';
    };

    // If user is not authenticated, show welcome page
    if (!auth?.user) {
        return (
            <>
                <Head title="Vehicle Refueling Management System" />
                
                <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
                    <div className="container mx-auto px-4 py-16">
                        <div className="text-center mb-12">
                            <div className="flex justify-center mb-6">
                                <div className="p-4 bg-blue-600 rounded-full">
                                    <Truck className="w-12 h-12 text-white" />
                                </div>
                            </div>
                            <h1 className="text-4xl font-bold text-gray-900 mb-4">
                                ⛽ Vehicle Refueling Management System
                            </h1>
                            <p className="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                                Streamline your fuel distribution process with our comprehensive management system. 
                                Perfect for distributors, sales teams, and shift operators.
                            </p>
                        </div>

                        <div className="grid md:grid-cols-3 gap-8 mb-12">
                            <Card className="text-center">
                                <CardHeader>
                                    <div className="mx-auto w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-4">
                                        <User className="w-6 h-6 text-green-600" />
                                    </div>
                                    <CardTitle>👨‍💼 Distributor Portal</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="text-sm text-gray-600 space-y-2">
                                        <li>• Create new refueling requests</li>
                                        <li>• Edit pending requests</li>
                                        <li>• Track request status</li>
                                        <li>• Manage delivery orders</li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card className="text-center">
                                <CardHeader>
                                    <div className="mx-auto w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                        <CheckCircle2 className="w-6 h-6 text-blue-600" />
                                    </div>
                                    <CardTitle>💼 Sales Dashboard</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="text-sm text-gray-600 space-y-2">
                                        <li>• Review pending requests</li>
                                        <li>• Approve or reject requests</li>
                                        <li>• Add rejection reasons</li>
                                        <li>• Monitor approval metrics</li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card className="text-center">
                                <CardHeader>
                                    <div className="mx-auto w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                                        <Truck className="w-6 h-6 text-purple-600" />
                                    </div>
                                    <CardTitle>🚛 Shift Operations</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="text-sm text-gray-600 space-y-2">
                                        <li>• View approved requests</li>
                                        <li>• Mark refueling as completed</li>
                                        <li>• Real-time status updates</li>
                                        <li>• Operations tracking</li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>

                        <div className="text-center">
                            <div className="space-x-4">
                                <Button asChild size="lg" className="bg-blue-600 hover:bg-blue-700">
                                    <a href={route('login')}>
                                        🚀 Login to Dashboard
                                    </a>
                                </Button>
                                <Button asChild variant="outline" size="lg">
                                    <a href={route('register')}>
                                        📝 Create Account
                                    </a>
                                </Button>
                            </div>
                            <p className="text-sm text-gray-500 mt-4">
                                Get started in seconds. Choose your role and begin managing refueling requests efficiently.
                            </p>
                        </div>
                    </div>
                </div>
            </>
        );
    }

    // Authenticated user view
    return (
        <>
            <Head title="Refueling Requests" />
            
            <AppShell>
                <div className="space-y-6">
                    <div className="flex justify-between items-start">
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">
                                ⛽ Refueling Management
                            </h1>
                            <p className="text-gray-600 mt-1">
                                Welcome back, {getRoleDisplayName(userRole)}! Manage your refueling requests below.
                            </p>
                        </div>
                        
                        {canCreateRequest && (
                            <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                                <DialogTrigger asChild>
                                    <Button className="bg-blue-600 hover:bg-blue-700">
                                        <Plus className="w-4 h-4 mr-2" />
                                        New Request
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Create Refueling Request</DialogTitle>
                                    </DialogHeader>
                                    <form onSubmit={handleCreateRequest} className="space-y-4">
                                        <div>
                                            <Label htmlFor="no_do">Delivery Order Number</Label>
                                            <Input
                                                id="no_do"
                                                value={data.no_do}
                                                onChange={(e) => setData('no_do', e.target.value)}
                                                placeholder="e.g., DO-2024-001"
                                                className={errors.no_do ? 'border-red-500' : ''}
                                            />
                                            {errors.no_do && (
                                                <p className="text-sm text-red-600 mt-1">{errors.no_do}</p>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="nopol">Vehicle Registration Number</Label>
                                            <Input
                                                id="nopol"
                                                value={data.nopol}
                                                onChange={(e) => setData('nopol', e.target.value)}
                                                placeholder="e.g., B 1234 ABC"
                                                className={errors.nopol ? 'border-red-500' : ''}
                                            />
                                            {errors.nopol && (
                                                <p className="text-sm text-red-600 mt-1">{errors.nopol}</p>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="distributor_name">Distributor Name</Label>
                                            <Input
                                                id="distributor_name"
                                                value={data.distributor_name}
                                                onChange={(e) => setData('distributor_name', e.target.value)}
                                                placeholder="e.g., PT Fuel Distribution"
                                                className={errors.distributor_name ? 'border-red-500' : ''}
                                            />
                                            {errors.distributor_name && (
                                                <p className="text-sm text-red-600 mt-1">{errors.distributor_name}</p>
                                            )}
                                        </div>

                                        <div className="flex justify-end space-x-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => setShowCreateModal(false)}
                                            >
                                                Cancel
                                            </Button>
                                            <Button type="submit" disabled={processing}>
                                                {processing ? 'Creating...' : 'Create Request'}
                                            </Button>
                                        </div>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        )}
                    </div>

                    {/* Requests List */}
                    <div className="space-y-4">
                        {requests?.data && requests.data.length > 0 ? (
                            requests.data.map((request) => (
                                <Card key={request.id} className="hover:shadow-md transition-shadow">
                                    <CardContent className="p-6">
                                        <div className="flex justify-between items-start mb-4">
                                            <div className="space-y-2">
                                                <div className="flex items-center gap-3">
                                                    <h3 className="font-semibold text-lg">
                                                        📋 {request.no_do}
                                                    </h3>
                                                    {getStatusBadge(request.status)}
                                                </div>
                                                <div className="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                                    <div className="flex items-center gap-2">
                                                        <Truck className="w-4 h-4" />
                                                        <span><strong>Vehicle:</strong> {request.nopol}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <User className="w-4 h-4" />
                                                        <span><strong>Distributor:</strong> {request.distributor_name}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="w-4 h-4" />
                                                        <span><strong>Created:</strong> {new Date(request.created_at).toLocaleDateString()}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <User className="w-4 h-4" />
                                                        <span><strong>Created by:</strong> {request.creator.name}</span>
                                                    </div>
                                                </div>
                                                
                                                {request.rejection_reason && (
                                                    <div className="mt-3 p-3 bg-red-50 rounded-lg border border-red-200">
                                                        <p className="text-sm text-red-800">
                                                            <strong>Rejection Reason:</strong> {request.rejection_reason}
                                                        </p>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="flex gap-2">
                                                {/* Distributor Actions */}
                                                {userRole === 'distributor' && request.status === 'pending' && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleEdit(request.id)}
                                                    >
                                                        ✏️ Edit
                                                    </Button>
                                                )}

                                                {/* Sales Actions */}
                                                {userRole === 'sales' && request.status === 'pending' && (
                                                    <>
                                                        <Button
                                                            variant="default"
                                                            size="sm"
                                                            onClick={() => handleApprove(request.id)}
                                                            className="bg-green-600 hover:bg-green-700"
                                                        >
                                                            ✅ Approve
                                                        </Button>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() => handleReject(request.id)}
                                                        >
                                                            ❌ Reject
                                                        </Button>
                                                    </>
                                                )}

                                                {/* Shift Actions */}
                                                {userRole === 'shift' && request.status === 'approved' && (
                                                    <Button
                                                        variant="default"
                                                        size="sm"
                                                        onClick={() => handleComplete(request.id)}
                                                        className="bg-purple-600 hover:bg-purple-700"
                                                    >
                                                        🚛 Mark Complete
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        ) : (
                            <Card>
                                <CardContent className="p-8 text-center">
                                    <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                        No refueling requests found
                                    </h3>
                                    <p className="text-gray-600">
                                        {canCreateRequest 
                                            ? "Get started by creating your first refueling request."
                                            : "There are no requests to display for your role at the moment."
                                        }
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>

                {/* Reject Modal */}
                <Dialog open={showRejectModal} onOpenChange={setShowRejectModal}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Reject Request</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleRejectSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="rejection_reason">Reason for Rejection</Label>
                                <Textarea
                                    id="rejection_reason"
                                    value={rejectData.rejection_reason}
                                    onChange={(e) => setRejectData('rejection_reason', e.target.value)}
                                    placeholder="Please provide a detailed reason for rejecting this request..."
                                    rows={4}
                                    className=""
                                />

                            </div>

                            <div className="flex justify-end space-x-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setShowRejectModal(false);
                                        resetReject();
                                    }}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" variant="destructive">
                                    Reject Request
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </AppShell>
        </>
    );
}