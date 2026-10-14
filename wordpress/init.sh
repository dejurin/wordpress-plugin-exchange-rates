#!/bin/sh
set -eu

started=$(date +%s)
printf '[1/3] Checking local WordPress installation\n'
if ! wp core is-installed --skip-plugins --skip-themes; then
    wp core install --quiet \
        --url="$DEV_SITE_URL" --title='Exchange Rates Development' \
        --admin_user="$WP_ADMIN_USER" --admin_email="$WP_ADMIN_EMAIL" \
        --admin_password="$WP_ADMIN_PASSWORD" --skip-email --skip-plugins --skip-themes
fi

printf '[2/3] Checking URL and activating Exchange Rates\n'
# Read the stored value; WP_HOME filters get_option('home') to the current URL.
stored_url=$(wp eval 'global $wpdb; echo $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", "home"));' --skip-plugins --skip-themes)
if [ "$stored_url" != "$DEV_SITE_URL" ]; then
    printf 'Existing site URL differs: %s. See docs/development.md before changing the port.\n' "$stored_url" >&2
    exit 1
fi
if ! wp plugin is-active exchange-rates; then
    wp plugin activate exchange-rates
fi
printf '[3/3] Complete in %ss. Site: %s; admin: %s/wp-admin/\n' \
    "$(($(date +%s) - started))" "$DEV_SITE_URL" "$DEV_SITE_URL"
