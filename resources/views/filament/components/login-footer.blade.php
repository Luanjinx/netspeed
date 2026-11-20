@if(request()->routeIs('*login*'))
    <div class="fi-simple-page-footer">
        <!-- Customer Portal Quick Notice Card -->
        <div class="fi-login-customer-card">
            <div class="fi-login-customer-text">
                <svg class="fi-login-info-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>
                <span>Pelanggan mencari portal cek tagihan?</span>
            </div>
            <a href="{{ route('login') }}" class="fi-login-customer-btn">
                <span>Masuk ke Portal Pelanggan</span>
                <span class="fi-login-arrow">→</span>
            </a>
        </div>

        <!-- Back to Home Link -->
        <div class="fi-login-back-wrapper">
            <a href="{{ url('/') }}" class="fi-login-back-link">
                <svg class="fi-login-back-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>
    </div>
@endif
