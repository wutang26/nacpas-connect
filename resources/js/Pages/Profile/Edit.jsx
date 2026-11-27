
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

import './Profile.css';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="profile-header">
                    <div>
                        <span className="profile-eyebrow">ACCOUNT SETTINGS</span>
                        <h2>My Profile</h2>
                        <p>Manage your personal information and account security.</p>
                    </div>
                </div>
            }
        >
            <Head title="My Profile" />

            <main className="profile-page">
                <div className="profile-container">

                    <div className="profile-intro">
                        <div className="profile-intro-icon">
                            <span>👤</span>
                        </div>

                        <div>
                            <h1>Account Settings</h1>
                            <p>
                                Keep your account information updated and secure.
                            </p>
                        </div>
                    </div>

                    <div className="profile-layout">

                        <aside className="profile-sidebar">
                            <div className="profile-sidebar-card">
                                <div className="profile-avatar">
                                    <span>👤</span>
                                </div>

                                <h3>Account Management</h3>
                                <p>
                                    Manage your profile, password and account
                                    preferences.
                                </p>

                                <div className="profile-sidebar-divider" />

                                <div className="profile-sidebar-item">
                                    <span>01</span>
                                    <div>
                                        <strong>Personal Information</strong>
                                        <small>Profile details</small>
                                    </div>
                                </div>

                                <div className="profile-sidebar-item">
                                    <span>02</span>
                                    <div>
                                        <strong>Security</strong>
                                        <small>Password management</small>
                                    </div>
                                </div>

                                <div className="profile-sidebar-item danger">
                                    <span>03</span>
                                    <div>
                                        <strong>Delete Account</strong>
                                        <small>Account removal</small>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <div className="profile-content">

                            <section className="profile-section">
                                <div className="profile-section-heading">
                                    <div className="profile-section-icon">
                                        <span>👤</span>
                                    </div>

                                    <div>
                                        <h2>Personal Information</h2>
                                        <p>
                                            Update your name, email address and
                                            other profile information.
                                        </p>
                                    </div>
                                </div>

                                <div className="profile-form-content">
                                    <UpdateProfileInformationForm
                                        mustVerifyEmail={mustVerifyEmail}
                                        status={status}
                                        className="profile-form"
                                    />
                                </div>
                            </section>

                            <section className="profile-section">
                                <div className="profile-section-heading">
                                    <div className="profile-section-icon">
                                        <span>🔒</span>
                                    </div>

                                    <div>
                                        <h2>Account Security</h2>
                                        <p>
                                            Change your password to keep your
                                            account protected.
                                        </p>
                                    </div>
                                </div>

                                <div className="profile-form-content">
                                    <UpdatePasswordForm className="profile-form" />
                                </div>
                            </section>

                            <section className="profile-section profile-delete-section">
                                <div className="profile-section-heading">
                                    <div className="profile-section-icon danger-icon">
                                        <span>⚠</span>
                                    </div>

                                    <div>
                                        <h2>Delete Account</h2>
                                        <p>
                                            Permanently remove your account and
                                            associated data.
                                        </p>
                                    </div>
                                </div>

                                <div className="profile-form-content">
                                    <DeleteUserForm className="profile-form" />
                                </div>
                            </section>

                        </div>
                    </div>

                    <div className="profile-footer">
                        <span>NACPASI</span>
                        <p>Connect. Manage. Grow.</p>
                    </div>

                </div>
            </main>
        </AuthenticatedLayout>
    );
}
