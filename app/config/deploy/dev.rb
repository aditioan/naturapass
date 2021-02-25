set :stage,     :dev

server 'naturapass.e-conception.fr', user: "deploy", roles: %w{web app db dev}

set :domain,    "naturapass.e-conception.fr"
set :branch,    "v2"

set :symfony_env,   "dev"

set :deploy_to,   "/var/www/html/" + fetch(:domain)

# Composer flags
set :composer_install_flags, "--no-ansi --no-interaction --no-progress --optimize-autoloader"

# Controllers to clear
set :controllers_to_clear, []

# Symfony console flags
set :symfony_console_flags, ""