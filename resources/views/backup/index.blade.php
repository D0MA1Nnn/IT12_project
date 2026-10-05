@extends('layouts.app')

@section('content')

@php
    $hasOnlineBackup = count($onlineBackups) > 0;
@endphp

<div class="backup-grid">

    <div class="card backup-card">
        <div class="backup-card-head">
            <span class="backup-icon offline">↓</span>

            <div>
                <span class="backup-pill offline">Offline</span>
                <h2>Download Backup</h2>
            </div>
        </div>

        <div class="backup-card-body">
            <p class="muted">
                Save a backup file to your computer, USB drive, or external storage.
            </p>
        </div>

        <a
            class="btn primary"
            href="{{ route('backup.create') }}"
            data-backup-confirm="link"
            data-confirm-title="Download backup file?"
            data-confirm-message="This will create a database backup and download it to your computer."
            data-confirm-button="Download Backup"
        >
            Download Backup File
        </a>
    </div>

    <div class="card backup-card">
        <div class="backup-card-head">
            <span class="backup-icon online">↥</span>

            <div>
                <span class="backup-pill online">Online</span>
                <h2>Online Backup Folder</h2>
            </div>
        </div>

        <div class="backup-card-body">
            <p class="muted">
                Create a backup file inside your Google Drive Desktop synced folder.
            </p>
        </div>

        @if($onlineBackupConfigured)
            <div class="backup-action-row">
                <form
                    method="POST"
                    action="{{ route('backup.google-drive.create') }}"
                    data-backup-confirm="form"
                    data-confirm-title="Save online backup?"
                    data-confirm-message="This will create a new online backup. The previous online backup file will be replaced."
                    data-confirm-button="Save Backup"
                >
                    @csrf
                    <button class="btn primary">
                        Save Backup to Online Folder
                    </button>
                </form>

                @if($hasOnlineBackup)
                    <span class="backup-save-status">
                        Currently saved
                    </span>
                @endif
            </div>
        @else
            <div class="backup-setup-box">
                <strong>Online backup folder is not configured yet.</strong>
                <span>Add ONLINE_BACKUP_PATH in your .env and make sure the folder exists.</span>
            </div>
        @endif
    </div>

    <div class="card backup-card">
        <div class="backup-card-head">
            <span class="backup-icon restore">↺</span>

            <div>
                <span class="backup-pill restore">Recovery</span>
                <h2>Restore from File</h2>
            </div>
        </div>

        <div class="backup-card-body">
            <p class="muted">
                Upload a valid backup file from your computer. This replaces the active database.
            </p>
        </div>

        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ route('backup.restore') }}"
            data-backup-confirm="form"
            data-confirm-title="Restore from selected file?"
            data-confirm-message="This will replace the active database with the selected backup file."
            data-confirm-button="Restore File"
        >
            @csrf

            <input
                class="input"
                type="file"
                name="backup"
                accept=".sqlite,.sql"
                required
            >

            <button class="btn secondary backup-action-button">
                Restore Selected File
            </button>
        </form>
    </div>

    <div class="card backup-card">
        <div class="backup-card-head">
            <span class="backup-icon online">↧</span>

            <div>
                <span class="backup-pill online">Online</span>
                <h2>Restore from Online Folder</h2>
            </div>
        </div>

        <div class="backup-card-body">
            <p class="muted">
                Restore one of the latest backups saved in your synced backup folder.
            </p>
        </div>

        @if(! $onlineBackupConfigured)
            <div class="backup-setup-box">
                <strong>Waiting for online backup folder setup.</strong>
                <span>Online backups will appear here after ONLINE_BACKUP_PATH is set.</span>
            </div>
        @elseif($onlineError)
            <div class="backup-setup-box danger">
                <strong>Unable to load Drive backups.</strong>
                <span>{{ $onlineError }}</span>
            </div>
        @elseif(count($onlineBackups) === 0)
            <div class="backup-setup-box">
                <strong>No online backups yet.</strong>
                <span>Create your first online backup above.</span>
            </div>
        @else
            <div class="backup-online-list">
                @foreach($onlineBackups as $backup)
                    <form
                        method="POST"
                        action="{{ route('backup.google-drive.restore') }}"
                        data-backup-confirm="form"
                        data-confirm-title="Restore online backup?"
                        data-confirm-message="This will replace the active database with the latest online backup file."
                        data-confirm-button="Restore Backup"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="file_id"
                            value="{{ $backup['id'] }}"
                        >

                        <div>
                            <strong>{{ $backup['name'] }}</strong>
                            <span>
                                {{ isset($backup['createdTime']) ? \Illuminate\Support\Carbon::parse($backup['createdTime'])->format('M d, Y g:i A') : 'Online Backup Folder' }}
                            </span>
                        </div>

                        <button class="btn light small">
                            Restore
                        </button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>

</div>

<div
    class="backup-confirm-overlay"
    id="backupConfirmModal"
    aria-hidden="true"
>
    <div
        class="backup-confirm-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="backupConfirmTitle"
        aria-describedby="backupConfirmMessage"
    >
        <div class="backup-confirm-icon">!</div>

        <div>
            <h2 id="backupConfirmTitle">Confirm action</h2>
            <p id="backupConfirmMessage">Are you sure you want to continue?</p>
        </div>

        <div class="backup-confirm-actions">
            <button
                type="button"
                class="btn light"
                id="backupConfirmCancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn primary"
                id="backupConfirmProceed"
            >
                Continue
            </button>
        </div>
    </div>
</div>

<style>
    .backup-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .backup-card {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 18px;
        min-height: 220px;
        padding: 24px;
        overflow: hidden;
        border: 1px solid #e4ebf5;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .backup-card::after {
        content: "";
        position: absolute;
        inset: auto -45px -65px auto;
        width: 150px;
        height: 150px;
        border-radius: 999px;
        background: #f1f6ff;
        opacity: 0.75;
        pointer-events: none;
    }

    .backup-card:hover {
        transform: translateY(-2px);
        border-color: #cfe0f7;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .backup-card h2 {
        margin: 6px 0 0;
        font-size: 22px;
        letter-spacing: -0.02em;
    }

    .backup-card-body {
        min-height: 42px;
        position: relative;
        z-index: 1;
    }

    .backup-card-body p {
        margin: 0;
        line-height: 1.55;
    }

    .backup-card-head {
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
        z-index: 1;
    }

    .backup-icon {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        font-weight: 900;
    }

    .backup-icon.offline {
        color: #1d4ed8;
        background: #eff6ff;
    }

    .backup-icon.online {
        color: #15803d;
        background: #ecfdf3;
    }

    .backup-icon.restore {
        color: #b45309;
        background: #fffbeb;
    }

    .backup-pill {
        display: inline-flex;
        width: fit-content;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
    }

    .backup-pill.offline {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .backup-pill.online {
        color: #15803d;
        background: #dcfce7;
    }

    .backup-pill.restore {
        color: #b45309;
        background: #fef3c7;
    }

    .backup-action-button {
        margin-top: 10px;
        width: 100%;
    }

    .backup-action-row {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .backup-card form {
        position: relative;
        z-index: 1;
        width: auto;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .backup-card > .btn.primary,
    .backup-action-row .btn.primary {
        width: auto;
        min-width: 245px;
        padding-inline: 22px;
    }

    .backup-card .btn {
        min-height: 42px;
        position: relative;
        z-index: 1;
    }

    .backup-card input[type="file"] {
        padding: 10px;
        background: #f8fafc;
    }

    .backup-save-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #15803d;
        font-size: 13px;
        font-weight: 900;
    }

    .backup-save-status::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #22c55e;
    }

    .backup-setup-box {
        display: grid;
        gap: 5px;
        padding: 14px;
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        background: #f8fafc;
        color: #64748b;
        font-size: 13px;
        position: relative;
        z-index: 1;
    }

    .backup-setup-box strong {
        color: #0f172a;
    }

    .backup-setup-box.danger {
        border-color: #fecdd3;
        background: #fff1f2;
        color: #9f1239;
    }

    .backup-online-list {
        display: grid;
        gap: 8px;
        max-height: 175px;
        overflow-y: auto;
        padding-right: 4px;
        position: relative;
        z-index: 1;
    }

    .backup-online-list form {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 12px;
        border: 1px solid #e6edf6;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: none;
    }

    .backup-online-list span {
        display: block;
        margin-top: 4px;
        color: #64748b;
        font-size: 12px;
    }

    .backup-confirm-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        border: 0 !important;
        border-radius: 0 !important;
        background: rgba(15, 23, 42, 0.58) !important;
        box-shadow: none !important;
        backdrop-filter: blur(2px);
    }

    .page-backup .main > .backup-confirm-overlay {
        border: 0 !important;
        border-radius: 0 !important;
        background: rgba(15, 23, 42, 0.58) !important;
        box-shadow: none !important;
    }

    .backup-confirm-overlay.is-open {
        display: flex;
    }

    .backup-confirm-modal {
        width: min(420px, 100%);
        display: grid;
        gap: 16px;
        padding: 26px;
        border-radius: 22px;
        background: #ffffff;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
        animation: backupConfirmIn 0.18s ease;
    }

    .backup-confirm-icon {
        width: 46px;
        height: 46px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: 24px;
        font-weight: 900;
    }

    .backup-confirm-modal h2 {
        margin: 0 0 7px;
        color: #0f172a;
        font-size: 24px;
        letter-spacing: -0.03em;
    }

    .backup-confirm-modal p {
        margin: 0;
        color: #64748b;
        line-height: 1.55;
    }

    .backup-confirm-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 4px;
    }

    .backup-confirm-actions .btn {
        min-height: 44px;
    }

    @keyframes backupConfirmIn {
        from {
            opacity: 0;
            transform: translateY(8px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @media (max-width: 900px) {
        .backup-grid {
            grid-template-columns: 1fr;
        }

        .backup-confirm-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('backupConfirmModal');
        const title = document.getElementById('backupConfirmTitle');
        const message = document.getElementById('backupConfirmMessage');
        const cancelButton = document.getElementById('backupConfirmCancel');
        const proceedButton = document.getElementById('backupConfirmProceed');
        let pendingAction = null;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            pendingAction = null;
        };

        const openModal = (trigger, action) => {
            title.textContent = trigger.dataset.confirmTitle || 'Confirm action';
            message.textContent = trigger.dataset.confirmMessage || 'Are you sure you want to continue?';
            proceedButton.textContent = trigger.dataset.confirmButton || 'Continue';
            pendingAction = action;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            proceedButton.focus();
        };

        document.querySelectorAll('[data-backup-confirm="link"]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();

                openModal(link, () => {
                    window.location.href = link.href;
                });
            });
        });

        document.querySelectorAll('form[data-backup-confirm="form"]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                openModal(form, () => {
                    form.dataset.confirmed = 'true';
                    form.submit();
                });
            });
        });

        cancelButton.addEventListener('click', closeModal);

        proceedButton.addEventListener('click', () => {
            if (pendingAction) {
                pendingAction();
            }

            closeModal();
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    });
</script>

@endsection
