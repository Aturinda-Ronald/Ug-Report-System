Patch added on 2025-08-29 06:19:25
Files inserted:
  - reports_system/subject_weights.php
  - reports_system/lib/results.php
  - reports_system/dev/test_compute.php

Usage:
  1) Upload entire zip to your server (or extract locally into your project).
  2) Visit /subject_weights.php as an admin to edit per-subject component weights that sum to 100%.
  3) Use compute_weighted_percentage(...) from /lib/results.php anywhere you need totals.
  4) Optional test: /dev/test_compute.php?student_id=1&subject_id=1&term_id=1&school_id=1

Notes:
  - subject_weights.php tries to include header.php/footer.php if present at project root.
  - All paths assume config at /config/config.php and DB tables created earlier.
