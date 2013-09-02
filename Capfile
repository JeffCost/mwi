require 'railsless-deploy'
require 'yaml'

load 'deploy'
load 'config/common'
load 'config/stage'

set :app_config, YAML.load(File.read(File.expand_path("../Cap.config.yml", __FILE__)))

set :application,                app_config['config']['app']
set :user,                       app_config['config']['user']
set :scm,                        app_config['config']['scm']
set :repository,                 app_config['config']['repository']
set :deploy_via,                 app_config['config']['deploy_via']
set :keep_releases,              app_config['config']['keep_releases']
set :normalize_asset_timestamps, app_config['config']['normalize_asset_timestamps']
ssh_options[:port] =             app_config['config']['port']
set :shared_path,                "#{app_config['config']['shared_path']}#{application}/shared"
set :timestamp_now,              (Time.now.strftime '%Y-%m-%d--%H:%M:%S')


# Ignore system, logs, etc...
set :shared_children, %w(storage public/media public/files public/storage)

role :web, "#{application}"                   # Your HTTP server, Apache/etc
role :app, "#{application}"                   # This may be the same as your `Web` server
role :db,  "#{application}", :primary => true # This is where Rails migrations will run

set :use_sudo, false
set :copy_exclude, [".git", ".DS_Store", ".gitignore", ".gitmodules", "Capfile", "config/deploy.rb", "storage", "public/media", "public/files"]


namespace :deploy do

    desc "Set Application configuration files"
    task :setconfig, :roles => :app, :except => { :no_release => true } do
        dbconfig = File.read(File.expand_path("../application/config/#{enviroment}/database.php", __FILE__))
        put "#{dbconfig}" ,"#{latest_release}/application/config/database.php"
    end

    desc "Backup Production Database"
    task :backupdb, :roles => :app, :except => { :no_release => true } do

        db_config = YAML.load(File.read(File.expand_path("../application/config/#{enviroment}/#{enviroment}.yml", __FILE__)))

        run "mkdir -p #{tmp_backups_path}#{release_name}/" unless remote_dir_exists?("#{tmp_backups_path}#{release_name}/")
        
        db_config.each do |key, value|
            if key == enviroment
                value.each do |key, db|
                    
                    # filename = "DB_RB4-#{release_name}_#{timestamp_now}-[#{db["name"]}].sql"
                    filename = "#{db["name"]}.sql"

                    # Remove old backup file
                    run "rm -rf #{tmp_backups_path}#{release_name}/#{filename}"
                    
                    logger.debug "Dumping database [#{db["name"]}]"
                    cmd = "mysqldump --add-drop-table --opt --compress -u #{db["user"]} --password=#{db["pass"]} --host=#{db["host"]} #{db["name"]} > #{tmp_backups_path}#{release_name}/#{filename}"
                    run(cmd) do |channel, stream, data|
                        puts data
                    end

                    # compress the file on the server
                    logger.debug "Compressing databases"
                    run "gzip -9 #{tmp_backups_path}#{release_name}/#{filename}"
                end
            end
        end

        on_rollback do
            db_config.each do |key, value|
                if key == enviroment
                    value.each do |key, db|
                        filename = "#{db["name"]}.sql"
                        run "rm -rf #{tmp_backups_path}#{release_name}/#{filename}"
                        run "rm -rf #{tmp_backups_path}#{release_name}/#{filename}.gz"
                    end
                end
            end
        end
    end

    desc "Backup Production Files"
    task :bkpfiles, :roles => :app, :except => { :no_release => true } do

        if remote_symdir_exists?("#{current_path}")
            
            logger.debug "Copying previous release to the [#{tmp_backups_path}#{release_name}] directory"
            run "mkdir -p #{tmp_backups_path}#{release_name}" unless remote_dir_exists?("#{tmp_backups_path}#{release_name}")

            set_touch_dirs("#{current_path}")
            set_touch_files("#{current_path}")
            
            run "rsync -avL --exclude 'storage' --delete #{current_path} #{tmp_backups_path}#{release_name}"


            logger.debug "Compressing backup files..."
            set :archive_name, "RB4-#{release_name}_#{timestamp_now}.tar.gz"
            run "cd #{tmp_backups_path} && tar cmzf - #{release_name}/ | gzip -c --best > #{backups_path}/#{archive_name}/"
        end

        # Remove tmp folder
        run "rm -rf #{tmp_backups_path}"
    end
    
    desc "Symlink shared media files."
    task :symlink_shared do
        # run "mkdir -p #{shared_path}/media" unless !File.exists?("#{shared_path}/media")
        # run "chmod 775 #{shared_path}/media/"
        # run "ln -nfs #{shared_path}/media #{latest_release}/public/media"
    end

    desc "Create storage folders if it does not exit"
    task :create_storage do
        
        storage_directories = %w(cache database logs sessions views work)

        if remote_dir_exists?("#{shared_path}/storage")
            storage_directories = %w(cache database logs sessions views work)
            storage_directories.each do |directory|
                run "mkdir -p #{shared_path}/storage/#{directory}" unless remote_dir_exists?("#{shared_path}/storage/#{directory}")
                run "chmod 777 #{shared_path}/storage/#{directory}"
            end
            run "ln -nfs #{shared_path}/storage #{latest_release}/storage"
            
            run "chmod 777 #{shared_path}/storage"
        end

    end

    desc "Clean up old backups."
    task :cleanup, :except => { :no_release => true } do
        # If this is the first deploy create the backups folder
        run "mkdir -p #{backups_path}" unless remote_dir_exists?("#{backups_path}")
        count = 5
        if count >= backups.length
            logger.important "no old backups to clean up"
        else
            logger.info "keeping #{count} of #{backups.length} backups"

        begin
            archives = (backups - backups.last(count)).map { |backup| File.join(backups_path, backup) }.join(" ")

            # fix permissions on the the files and directories before removing them
            archives.split(" ").each do |backup|
                set_perms_dirs("#{backup}", 755) if File.directory?(backup)
                set_perms_files("#{backup}", 644)
            end

            run "rm -rf #{archives}"

            rescue Exception => e
                logger.important e.message
            end
        end
    end
end