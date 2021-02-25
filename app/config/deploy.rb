####
#
# Fichier de déploiement de l'application NaturaPasst
#
####

# Password deploy: OE2y[VB103<Y#7Q
set :user,                  "deploy"
set :group,                 "www-data"

set :pty,                   true
set :ssh_options, { forward_agent: true }

# Git
set :scm,         :git
set :repo_url,  "git@github.com:e-Conception/NaturaPass.git"
set :deploy_via, :remote_cache

# Symfony application path
set :app_path,              "app"

# Symfony web path
set :web_path,              "web"

# Symfony paths
set :log_path,              fetch(:app_path) + "/ "
set :cache_path,            fetch(:app_path) + "/cache"
set :app_config_path,       fetch(:app_path) + "/config"
set :symfony_console_path,  fetch(:app_path) + "/console"

# Files that need to remain the same between deploys
set :linked_files,          [fetch(:app_config_path) + "/parameters.yml"]

# Dirs that need to remain the same between deploys (shared dirs)
set :linked_dirs,           [fetch(:web_path) + "/uploads", "doc"]

# Assets install path
set :assets_install_path,   fetch(:web_path) + "/static"
set :assetic_dump_flags,  '--env=prod'

fetch(:default_env).merge!(symfony_env: fetch(:symfony_env))

set :log_level, :info

before 'deploy:updated',    'symfony:maintenance:lock'

namespace :symfony do
    namespace :maintenance do
        task :lock do
            on roles(:app) do
                if test("[ -f #{current_path}/app/console ]")
                    execute "php #{current_path}/app/console lexik:maintenance:lock --no-interaction"
                end
             end
        end

        task :unlock do
            on roles(:app) do
                if test("[ -f #{current_path}/app/console ]")
                    execute "php #{current_path}/app/console lexik:maintenance:unlock --no-interaction"
                end
            end
        end
    end

    namespace :web do
        task :assetclean do
            on roles(:app) do
                execute "php #{release_path}/app/console assetic:dump  web/static --env=prod --no-debug"
                execute "php #{release_path}/app/console assets:install web/static --env=prod --no-debug"
                execute "php #{release_path}/app/console cache:clear --env=prod"
            end
        end
        task :permissions do
            on roles(:app) do
                info "Setting up the permissions"

                execute "chmod -R g+wx #{release_path}/app/cache #{release_path}/app/logs"
                execute "chmod g+x #{release_path} #{release_path}/web #{release_path}/web/uploads"

                execute "chmod o+x,g+x #{release_path}/bin/nodeserver.sh"

                execute "setfacl -R -m u:#{fetch(:group)}:rwX -m u:#{fetch(:user)}:rwX #{release_path}/app/cache #{release_path}/app/logs"
                execute "setfacl -dR -m u:#{fetch(:group)}:rwX -m u:#{fetch(:user)}:rwX #{release_path}/app/cache #{release_path}/app/logs"
            end
        end

        task :translation do
            on roles(:web) do
                info "Dumping js translations to web/js"

                invoke 'symfony:console', 'bazinga:js-translation:dump', 'web/js'
            end
        end

        task :routes do
            on roles(:web) do
                info "Dumping js routes to web/static/js"

                execute "php #{release_path}/app/console fos:js-routing:dump --target #{release_path}/web/static/js/fos_js_routes.js"
            end
        end

        task :npm do
            on roles(:web) do
                info "Doing npm install"

                execute "npm --prefix #{release_path} install #{release_path}"
            end
        end

        task :grunt do
            on roles(:web) do
                info "Doing grunt"

                execute "grunt --gruntfile #{release_path}/Gruntfile.js"
            end
        end
    end

    namespace :doctrine do
        task :migrations do
            on roles(:db) do
                migrations = capture("php #{release_path}/app/console doctrine:migrations:status | grep -i 'new migrations' | awk '{print $4}'")

                if migrations != "0"
                    info "Doing the doctrine migrations"
                    invoke 'symfony:console', 'doctrine:migrations:migrate', '--no-interaction'
                end
            end
        end
    end

    namespace :assets do
        task :mkdir do
            on roles(:web) do
                info "Creating the assets dir"

                execute :mkdir, "-p", "#{release_path}/#{fetch(:web_path)}/static"
            end
        end
    end
end

namespace :naturapass do
    namespace :documentation do
        task :generate do
            on roles(:dev) do
                info "Generation documentation"

                execute "apigen --config #{release_path}/apigen.neon"
            end
        end
    end

    namespace :phpunit do
        task :do do
            on roles(:dev) do
                info "Doing unit testing"

                execute "#{release_path}/bin/phpunit -c #{release_path}/app"
            end
        end
    end

    namespace :nodejs do
        task :restart do
            on roles(:app) do
                execute "#{release_path}/bin/nodeserver.sh restart --env=#{fetch(:symfony_env)}", pty: false
            end
        end
    end

    namespace :database do
        task :dump do
            on roles(:db) do
                info "Dumping the database to a backup file"

                execute :mkdir, "-p", "#{deploy_to}/backups"

                require 'yaml'

                database = YAML::load(capture("cat #{shared_path}/app/config/parameters.yml"))['parameters']
                filename = "database.#{fetch(:branch)}.#{Time.now.strftime '%Y-%m-%d_%H:%M:%S'}.sql.bz2"

                mysqldump_cmd = "mysqldump -u #{database['database_user']} --password=#{database['database_password']} #{database['database_name']}"
                mysqldump_cmd += " | gzip > #{deploy_to}/backups/#{filename}"

                execute mysqldump_cmd
            end
        end
    end

    namespace :sslmailer do
        task :mv do
            on roles(:app) do
                if test("[ -f #{release_path}/app/file/StreamBuffer.php ]")
                    execute "mv #{release_path}/app/file/StreamBuffer.php #{release_path}/vendor/swiftmailer/swiftmailer/lib/classes/Swift/Transport "
                end
             end
        end
    end
end

after 'deploy:updated',     'symfony:assets:mkdir'

after 'deploy:updated',     'naturapass:database:dump'
after 'deploy:updated',     'symfony:doctrine:migrations'
after 'deploy:updated',     'naturapass:sslmailer:mv'

after 'deploy:updated',     'symfony:web:translation'
after 'deploy:updated',     'symfony:web:routes'
after 'deploy:updated',     'symfony:web:npm'
after 'deploy:updated',     'symfony:web:grunt'
# after 'deploy:updated',     'symfony:assetic:dump'
after 'deploy:updated',     'symfony:assets:install'

after 'deploy:updated',     'symfony:web:permissions'
after 'deploy:updated',     'naturapass:nodejs:restart'

after 'deploy:updated',     'deploy:cleanup'

after 'deploy:updated',     'naturapass:documentation:generate'
#after 'deploy:updated',     'naturapass:phpunit:do'

#after 'deploy:updated',    'symfony:maintenance:unlock'