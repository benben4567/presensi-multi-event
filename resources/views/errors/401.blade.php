<x-error-page
    code="401"
    icon="tabler-lock"
    tone="warning"
    title="Perlu Masuk Terlebih Dahulu"
    message="Anda perlu masuk ke akun Anda untuk mengakses halaman ini."
    home-label="Ke Halaman Masuk"
    :home-href="route('login')"
/>
