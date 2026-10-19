<?php

// API-only backend: tidak ada halaman web. (Tanpa closure agar `route:cache` bisa jalan;
// path yang tidak dikenal sudah menghasilkan 404 secara default.)
