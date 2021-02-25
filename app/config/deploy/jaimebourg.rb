set :stage,     :production

server 'appli.jaime-bourg.fr', user: "deploy", roles: %w{web app db}

set :domain,    "appli.jaime-bourg.fr"
set :branch,    "v2"

set :symfony_env, "prod"

set :deploy_to,   "/var/www/html/" + fetch(:domain)

# Composer flags
set :composer_install_flags, "--no-ansi --no-interaction --no-progress --no-dev --optimize-autoloader"

# Controllers to clear
#set :controllers_to_clear, ["app_*.php", "config.php"]
set :controllers_to_clear, ["config.php"]


# Symfony console flags
set :symfony_console_flags, "--no-debug"