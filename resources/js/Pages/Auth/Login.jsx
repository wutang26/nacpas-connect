
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import './Login.css';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            <div className="login-container">
                <div className="login-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to your NACPAS Connect account</p>
                </div>

                {status && (
                    <div className="login-status">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="login-form">
                    <div className="login-field">
                        <label htmlFor="email">Email Address</label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            placeholder="Enter your email"
                            autoComplete="username"
                            autoFocus
                            onChange={(e) =>
                                setData('email', e.target.value)
                            }
                        />

                        {errors.email && (
                            <span className="login-error">
                                {errors.email}
                            </span>
                        )}
                    </div>

                    <div className="login-field">
                        <label htmlFor="password">Password</label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            placeholder="Enter your password"
                            autoComplete="current-password"
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />

                        {errors.password && (
                            <span className="login-error">
                                {errors.password}
                            </span>
                        )}
                    </div>

                    <div className="login-options">
                        <label className="remember-label">
                            <input
                                type="checkbox"
                                name="remember"
                                checked={data.remember}
                                onChange={(e) =>
                                    setData('remember', e.target.checked)
                                }
                            />
                            <span>Remember me</span>
                        </label>

                        {canResetPassword && (
                            <Link
                                href="/forgot-password"
                                className="forgot-password"
                            >
                                Forgot password?
                            </Link>
                        )}
                    </div>

                    <button
                        type="submit"
                        className="login-button"
                        disabled={processing}
                    >
                        {processing ? 'Signing in...' : 'Sign in'}
                    </button>
                </form>

                <div className="login-footer">
                    <p>
                        NACPAS Connect
                        <span> | </span>
                        Powered by NAC Technologies
                    </p>
                </div>
            </div>
        </GuestLayout>
    );
}
