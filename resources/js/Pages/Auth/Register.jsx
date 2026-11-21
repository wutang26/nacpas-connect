
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import './Register.css';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post('/register', {
            onFinish: () => {
                reset('password', 'password_confirmation');
            },
        });
    };

    return (
        <GuestLayout>
            <Head title="Create Account - NACPAS Connect" />

            <div className="register-page">
                <div className="register-card">

                    {/* Header */}
                    <div className="register-header">
                        <div className="register-icon">
                            <span>✦</span>
                        </div>

                        <div>
                            <h2>Create your account</h2>
                            <p>
                                Join NACPAS Connect and manage your WiFi
                                business smarter.
                            </p>
                        </div>
                    </div>

                    {/* Form */}
                    <form onSubmit={submit} className="register-form">

                        {/* Name */}
                        <div className="register-field">
                            <label htmlFor="name">
                                Full Name
                            </label>

                            <div className={`register-input-wrapper ${errors.name ? 'has-error' : ''}`}>
                                <span className="register-input-icon">👤</span>

                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    placeholder="Enter your full name"
                                    value={data.name}
                                    autoComplete="name"
                                    autoFocus
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    required
                                />
                            </div>

                            {errors.name && (
                                <small className="register-error">
                                    {errors.name}
                                </small>
                            )}
                        </div>

                        {/* Email */}
                        <div className="register-field">
                            <label htmlFor="email">
                                Email Address
                            </label>

                            <div className={`register-input-wrapper ${errors.email ? 'has-error' : ''}`}>
                                <span className="register-input-icon">✉</span>

                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    placeholder="example@email.com"
                                    value={data.email}
                                    autoComplete="username"
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    required
                                />
                            </div>

                            {errors.email && (
                                <small className="register-error">
                                    {errors.email}
                                </small>
                            )}
                        </div>

                        {/* Password */}
                        <div className="register-field">
                            <label htmlFor="password">
                                Password
                            </label>

                            <div className={`register-input-wrapper ${errors.password ? 'has-error' : ''}`}>
                                <span className="register-input-icon">🔒</span>

                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Create a strong password"
                                    value={data.password}
                                    autoComplete="new-password"
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    required
                                />
                            </div>

                            {errors.password && (
                                <small className="register-error">
                                    {errors.password}
                                </small>
                            )}
                        </div>

                        {/* Confirm Password */}
                        <div className="register-field">
                            <label htmlFor="password_confirmation">
                                Confirm Password
                            </label>

                            <div className={`register-input-wrapper ${errors.password_confirmation ? 'has-error' : ''}`}>
                                <span className="register-input-icon">🔐</span>

                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    placeholder="Confirm your password"
                                    value={data.password_confirmation}
                                    autoComplete="new-password"
                                    onChange={(e) =>
                                        setData(
                                            'password_confirmation',
                                            e.target.value
                                        )
                                    }
                                    required
                                />
                            </div>

                            {errors.password_confirmation && (
                                <small className="register-error">
                                    {errors.password_confirmation}
                                </small>
                            )}
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            className="register-submit-button"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <span className="register-spinner"></span>
                                    Creating Account...
                                </>
                            ) : (
                                <>
                                    Create Account
                                    <span>→</span>
                                </>
                            )}
                        </button>
                    </form>

                    {/* Login link */}
                    <div className="register-login">
                        <span>Already have an account?</span>

                        <Link href="/login">
                            Sign in
                        </Link>
                    </div>

                    {/* Security */}
                    <div className="register-security">
                        <span>🔒</span>
                        Your information is protected and secure.
                    </div>

                </div>

                <div className="register-bottom-text">
                    © {new Date().getFullYear()} NACPAS Connect.
                    All rights reserved.
                </div>
            </div>
        </GuestLayout>
    );
}
