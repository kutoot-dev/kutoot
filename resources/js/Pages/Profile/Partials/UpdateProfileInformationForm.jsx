import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            mobile: user.mobile,
            gender: user.gender,
            country: user.country,
            state: user.state,
            city: user.city,
            pin_code: user.pin_code,
            full_address: user.full_address,
            profile_picture: null,
        });

    const submit = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Profile Information
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Update your account's profile information and email address.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="name" value="Name" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        autoComplete="username"
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                <div>
                    <InputLabel htmlFor="mobile" value="Mobile" />

                    <TextInput
                        id="mobile"
                        className="mt-1 block w-full"
                        value={data.mobile}
                        onChange={(e) => setData('mobile', e.target.value)}
                        autoComplete="tel"
                    />

                    <InputError className="mt-2" message={errors.mobile} />
                </div>

                <div>
                    <InputLabel htmlFor="gender" value="Gender" />
                    <select
                        id="gender"
                        className="mt-1 block w-full rounded-md border-gray-300"
                        value={data.gender || ''}
                        onChange={(e) => setData('gender', e.target.value)}
                    >
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                    <InputError className="mt-2" message={errors.gender} />
                </div>

                <div>
                    <InputLabel htmlFor="country" value="Country" />
                    <TextInput
                        id="country"
                        className="mt-1 block w-full"
                        value={data.country}
                        onChange={(e) => setData('country', e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.country} />
                </div>

                <div>
                    <InputLabel htmlFor="state" value="State" />
                    <TextInput
                        id="state"
                        className="mt-1 block w-full"
                        value={data.state}
                        onChange={(e) => setData('state', e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.state} />
                </div>

                <div>
                    <InputLabel htmlFor="city" value="City" />
                    <TextInput
                        id="city"
                        className="mt-1 block w-full"
                        value={data.city}
                        onChange={(e) => setData('city', e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.city} />
                </div>

                <div>
                    <InputLabel htmlFor="pin_code" value="Pin code" />
                    <TextInput
                        id="pin_code"
                        className="mt-1 block w-full"
                        value={data.pin_code}
                        onChange={(e) => setData('pin_code', e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.pin_code} />
                </div>

                <div>
                    <InputLabel htmlFor="full_address" value="Full Address" />
                    <textarea
                        id="full_address"
                        className="mt-1 block w-full border-gray-300 rounded-md"
                        value={data.full_address}
                        onChange={(e) => setData('full_address', e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.full_address} />
                </div>

                <div>
                    <InputLabel htmlFor="profile_picture" value="Profile Picture" />
                    {user.profile_picture_url && (
                        <img
                            src={user.profile_picture_url}
                            alt="Current avatar"
                            className="h-16 w-16 rounded-full object-cover mb-2"
                        />
                    )}
                    <input
                        id="profile_picture"
                        type="file"
                        className="mt-1 block w-full"
                        onChange={(e) => setData('profile_picture', e.target.files[0])}
                    />
                    <InputError className="mt-2" message={errors.profile_picture} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
