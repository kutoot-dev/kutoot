import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function OtpLogin({ status, debugOtp }) {
    const [otpSent, setOtpSent] = useState(false);

    const sendForm = useForm({
        identifier: '',
    });

    const verifyForm = useForm({
        identifier: '',
        otp: '',
    });

    const handleSendOtp = (e) => {
        e.preventDefault();

        sendForm.post(route('otp-login.send'), {
            preserveScroll: true,
            onSuccess: (page) => {
                setOtpSent(true);
                verifyForm.setData('identifier', sendForm.data.identifier);

                // Auto-fill OTP in debug mode
                if (page.props.debugOtp) {
                    verifyForm.setData((prev) => ({
                        ...prev,
                        identifier: sendForm.data.identifier,
                        otp: page.props.debugOtp,
                    }));
                }
            },
        });
    };

    const handleVerifyOtp = (e) => {
        e.preventDefault();

        verifyForm.post(route('otp-login.verify'), {
            onFinish: () => verifyForm.reset('otp'),
        });
    };

    const handleResendOtp = () => {
        sendForm.post(route('otp-login.send'), {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.debugOtp) {
                    verifyForm.setData('otp', page.props.debugOtp);
                }
            },
        });
    };

    const handleChangeIdentifier = () => {
        setOtpSent(false);
        verifyForm.reset();
        sendForm.reset();
    };

    return (
        <GuestLayout>
            <Head title="OTP Login" />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            {!otpSent ? (
                <form onSubmit={handleSendOtp}>
                    <div className="mb-4 text-center">
                        <h2 className="text-lg font-semibold text-lucky-700">
                            Login with OTP
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Enter your email or mobile number to receive a
                            one-time password.
                        </p>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="identifier"
                            value="Email or Mobile"
                        />

                        <TextInput
                            id="identifier"
                            type="text"
                            name="identifier"
                            value={sendForm.data.identifier}
                            className="mt-1 block w-full"
                            isFocused={true}
                            placeholder="email@example.com or 9876543210"
                            onChange={(e) =>
                                sendForm.setData('identifier', e.target.value)
                            }
                        />

                        <InputError
                            message={sendForm.errors.identifier}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-4 flex items-center justify-between">
                        <div className="flex flex-col gap-1">
                            <Link
                                href={route('password-login')}
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-lucky-500 focus:ring-offset-2"
                            >
                                Use password instead
                            </Link>
                            <Link
                                href={route('register')}
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-lucky-500 focus:ring-offset-2"
                            >
                                Create account
                            </Link>
                        </div>

                        <PrimaryButton disabled={sendForm.processing}>
                            Send OTP
                        </PrimaryButton>
                    </div>
                </form>
            ) : (
                <form onSubmit={handleVerifyOtp}>
                    <div className="mb-4 text-center">
                        <h2 className="text-lg font-semibold text-lucky-700">
                            Enter OTP
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            We sent a 6-digit code to{' '}
                            <span className="font-medium text-lucky-600">
                                {sendForm.data.identifier}
                            </span>
                        </p>
                    </div>

                    <div>
                        <InputLabel htmlFor="otp" value="One-Time Password" />

                        <TextInput
                            id="otp"
                            type="text"
                            name="otp"
                            value={verifyForm.data.otp}
                            className="mt-1 block w-full text-center text-2xl tracking-[0.5em]"
                            isFocused={true}
                            maxLength={6}
                            placeholder="000000"
                            autoComplete="one-time-code"
                            onChange={(e) =>
                                verifyForm.setData(
                                    'otp',
                                    e.target.value.replace(/\D/g, ''),
                                )
                            }
                        />

                        <InputError
                            message={verifyForm.errors.otp}
                            className="mt-2"
                        />
                        <InputError
                            message={verifyForm.errors.identifier}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-4 flex items-center justify-between">
                        <div className="flex flex-col gap-1">
                            <button
                                type="button"
                                onClick={handleResendOtp}
                                disabled={sendForm.processing}
                                className="text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none"
                            >
                                Resend OTP
                            </button>
                            <button
                                type="button"
                                onClick={handleChangeIdentifier}
                                className="text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none"
                            >
                                Change email/mobile
                            </button>
                        </div>

                        <PrimaryButton disabled={verifyForm.processing}>
                            Verify & Login
                        </PrimaryButton>
                    </div>
                </form>
            )}
        </GuestLayout>
    );
}
