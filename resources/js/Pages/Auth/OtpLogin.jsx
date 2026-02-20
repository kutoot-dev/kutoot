import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function OtpLogin({ status, debugOtp, otpLength = 6 }) {
    const [otpSent, setOtpSent] = useState(false);
    const [otpDigits, setOtpDigits] = useState(Array(otpLength).fill(''));

    const digitRefs = useRef([]);

    const sendForm = useForm({
        identifier: '',
    });

    const verifyForm = useForm({
        identifier: '',
        otp: '',
    });

    // Sync digits to verifyForm whenever they change
    useEffect(() => {
        verifyForm.setData('otp', otpDigits.join(''));
    }, [otpDigits]);

    // When debug OTP is returned, auto-fill all boxes
    useEffect(() => {
        if (debugOtp && debugOtp.length === otpLength) {
            setOtpDigits(debugOtp.split(''));
        }
    }, [debugOtp]);

    const handleSendOtp = (e) => {
        e.preventDefault();

        sendForm.post(route('otp-login.send'), {
            preserveScroll: true,
            onSuccess: (page) => {
                setOtpSent(true);
                setOtpDigits(Array(otpLength).fill(''));
                verifyForm.setData('identifier', sendForm.data.identifier);

                if (page.props.debugOtp) {
                    const digits = page.props.debugOtp.split('');
                    setOtpDigits(digits);
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
            onFinish: () => {
                verifyForm.reset('otp');
                setOtpDigits(Array(otpLength).fill(''));
            },
        });
    };

    const handleResendOtp = () => {
        sendForm.post(route('otp-login.send'), {
            preserveScroll: true,
            onSuccess: (page) => {
                setOtpDigits(Array(otpLength).fill(''));
                if (page.props.debugOtp) {
                    setOtpDigits(page.props.debugOtp.split(''));
                    verifyForm.setData('otp', page.props.debugOtp);
                }
            },
        });
    };

    const handleChangeIdentifier = () => {
        setOtpSent(false);
        setOtpDigits(Array(otpLength).fill(''));
        verifyForm.reset();
        sendForm.reset();
    };

    const handleDigitChange = (index, value) => {
        if (value && !/^\d+$/.test(value)) return;

        const newDigits = [...otpDigits];
        newDigits[index] = value.slice(-1);
        setOtpDigits(newDigits);

        if (value && index < otpLength - 1) {
            digitRefs.current[index + 1]?.focus();
        }
    };

    const handleKeyDown = (index, e) => {
        if (e.key === 'Backspace' && !otpDigits[index] && index > 0) {
            digitRefs.current[index - 1]?.focus();
        }
    };

    const handlePaste = (e) => {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, otpLength);

        if (pastedData) {
            const newDigits = Array(otpLength).fill('');
            for (let i = 0; i < pastedData.length; i++) {
                newDigits[i] = pastedData[i];
            }
            setOtpDigits(newDigits);

            const nextFocusIndex = Math.min(pastedData.length, otpLength - 1);
            digitRefs.current[nextFocusIndex]?.focus();
        }
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
                            We sent a {otpLength}-digit code to{' '}
                            <span className="font-medium text-lucky-600">
                                {sendForm.data.identifier}
                            </span>
                        </p>
                    </div>

                    <div>
                        <InputLabel htmlFor="otp-0" value="One-Time Password" />

                        <div className="mt-2 flex justify-center gap-2">
                            {Array.from({ length: otpLength }, (_, index) => (
                                <input
                                    key={index}
                                    id={`otp-${index}`}
                                    ref={(el) => (digitRefs.current[index] = el)}
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={1}
                                    value={otpDigits[index]}
                                    onChange={(e) => handleDigitChange(index, e.target.value)}
                                    onKeyDown={(e) => handleKeyDown(index, e)}
                                    onPaste={index === 0 ? handlePaste : undefined}
                                    autoFocus={index === 0}
                                    autoComplete={index === 0 ? 'one-time-code' : 'off'}
                                    className="h-12 w-12 rounded-lg border border-gray-300 text-center text-xl font-bold shadow-sm focus:border-lucky-500 focus:outline-none focus:ring-2 focus:ring-lucky-500"
                                />
                            ))}
                        </div>

                        <InputError
                            message={verifyForm.errors.otp}
                            className="mt-2 text-center"
                        />
                        <InputError
                            message={verifyForm.errors.identifier}
                            className="mt-2 text-center"
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
                            Verify &amp; Login
                        </PrimaryButton>
                    </div>
                </form>
            )}
        </GuestLayout>
    );
}
