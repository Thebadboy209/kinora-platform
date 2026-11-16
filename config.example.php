<?php
declare(strict_types=1);
// If deployed in a subfolder, for example /kinora, set BASE_PATH to '/kinora'.
const BASE_PATH = '';
// CHANGE THIS before installation. Admin setup will reject the default value.
const INSTALL_SECRET = 'CHANGE_THIS_TO_A_LONG_PRIVATE_INSTALL_SECRET';
// Move STORAGE_PATH outside the public web root on hosts that support it.
const STORAGE_PATH = __DIR__ . '/storage';
const PONE_UPLOAD_URL = 'https://pone.rs/upload';
const PONE_LIMIT_BYTES = 1073741824;
const MAX_DEMO_COMMENTS = 50;
