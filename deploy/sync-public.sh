#!/bin/bash
#
# Sync this Laravel app's public/ directory into the sibling public_html/
# document root (the standard cPanel-style layout: the app repo and
# public_html live as sibling directories, e.g.
#   /home/user/thekan_erp    <- this repo (git pull happens here)
#   /home/user/public_html   <- the actual document root the domain serves
#
# Run this from the app root (thekan_erp) after every `git pull` that
# touches anything under public/ (CSS/JS/images, or new files).
#
# index.php is deliberately EXCLUDED from the sync. The public_html/index.php
# on this server is hand-tuned with a relative path back to this app
# directory (require __DIR__.'/../thekan_erp/vendor/autoload.php', or
# similar) - it does NOT match this repo's own public/index.php, which
# assumes public/ sits directly inside the app root one level up. Overwriting
# it with a blind copy would break every page on the live site. If
# public/index.php ever needs a real change (rare - it's Laravel's
# bootstrap file), edit public_html/index.php by hand on the server instead
# of syncing it.

set -e

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PUBLIC_HTML="${1:-$APP_ROOT/../public_html}"

if [ ! -d "$PUBLIC_HTML" ]; then
    echo "public_html not found at: $PUBLIC_HTML"
    echo "Usage: bash deploy/sync-public.sh [path-to-public_html]"
    echo "  (defaults to ../public_html relative to this app's root)"
    exit 1
fi

echo "App root:    $APP_ROOT"
echo "Syncing:     $APP_ROOT/public/  ->  $PUBLIC_HTML/"
echo "Excluding:   index.php (server-specific, never overwritten)"
echo ""

# Deliberately NOT using --delete: this script has no visibility into what
# else might legitimately live in public_html on the real server (SSL
# verification files, other apps, manually-placed uploads, etc.), so it only
# adds/updates files that come from this repo's public/ and never removes
# anything from the destination. If a file gets deleted from public/ in this
# repo, remove its counterpart from public_html by hand.
if command -v rsync >/dev/null 2>&1; then
    rsync -av \
        --exclude='index.php' \
        "$APP_ROOT/public/" "$PUBLIC_HTML/"
else
    echo "(rsync not found - falling back to cp; index.php is still preserved)"
    SAVED_INDEX=""
    if [ -f "$PUBLIC_HTML/index.php" ]; then
        SAVED_INDEX="$(mktemp)"
        cp "$PUBLIC_HTML/index.php" "$SAVED_INDEX"
    fi

    cp -R "$APP_ROOT/public/." "$PUBLIC_HTML/"

    if [ -n "$SAVED_INDEX" ]; then
        cp "$SAVED_INDEX" "$PUBLIC_HTML/index.php"
        rm -f "$SAVED_INDEX"
    else
        rm -f "$PUBLIC_HTML/index.php"
        echo "NOTE: public_html had no existing index.php to preserve - the one"
        echo "      just copied from this repo's public/ is NOT server-tuned."
        echo "      Fix public_html/index.php by hand before this site will work."
    fi
fi

echo ""
echo "Done."
