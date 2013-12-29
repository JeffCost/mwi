require 'railsless-deploy'
require 'yaml'

load 'deploy'
load 'config/common'
load 'config/production'
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
set :local_db_backup_path,       app_config['config']['local_db_backup_path']


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
                    run "mysqldump --add-drop-table --opt --compress -h #{db["host"]} -u #{db["user"]} -p #{db["name"]} "+
                    "> #{tmp_backups_path}#{release_name}/#{filename}" do |ch, _, out| 
                        if out =~ /^Enter password: /
                            ch.send_data "#{db["pass"]}\n"
                        else
                            puts out 
                        end
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
            run "cd #{tmp_backups_path} && tar cmzf - #{release_name}/ | gzip -c --best > #{backups_path}/#{archive_name}"
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
                set_perms_files("#{backup}", 644) if File.directory?(backup)
            end

            run "rm -rf #{archives}"

            rescue Exception => e
                logger.important e.message
            end
        end
    end

    desc "Create a compressed MySQL dumpfile of the remote database"
    task :remote_create_dump, :roles => :db do
        db_config = YAML.load(File.read(File.expand_path("../application/config/#{enviroment}/#{enviroment}.yml", __FILE__)))
        db_config.each do |key, value|
            if key == enviroment
                value.each do |key, db|
                    
                    filename = "#{db["name"]}.sql"
                    logger.debug "Removing any old remote dump file if any ..."
                    run "rm -rf /tmp/#{filename} /tmp/#{filename}.gz"
                    logger.debug "Dumping remote database [#{db["name"]}]"
                    
                    run "mysqldump --add-drop-table --opt --compress -h #{db["host"]} -u #{db["user"]} -p #{db["name"]} "+
                        "> /tmp/#{filename}" do |ch, _, out| 
                            if out =~ /^Enter password: /
                                ch.send_data "#{db["pass"]}\n"
                            else
                                puts out 
                            end
                        end

                    logger.debug "Compressing remote database [#{db["name"]}]"
                    run "gzip -9 /tmp/#{filename}"
                end
            end
        end
    end

    desc "Download remotely created MySQL dumpfile to local machine via SCP"
    task :remote_get_dump, :roles => :db do
        db_config = YAML.load(File.read(File.expand_path("../application/config/#{enviroment}/#{enviroment}.yml", __FILE__)))
        db_config.each do |key, value|
            if key == enviroment
                value.each do |key, db|
                    filename = "#{db["name"]}.sql.gz"
                    logger.debug "Downloading remote database dump [/tmp/#{filename}]"
                    system("scp -P #{app_config['config']['port']} #{user}@#{application}:/tmp/#{filename} /tmp/")
                    logger.debug "Removing remote database dump from [/tmp/#{filename}]"
                    run "rm -rf /tmp/#{filename}"
                end
            end
        end
    end

    desc "Restore remotely created MySQL dumpfile to local database"
    task :local_restore_dump, :roles => :db do
        db_local_config = YAML.load(File.read(File.expand_path("../application/config/develop/develop.yml", __FILE__)))
        db_config       = YAML.load(File.read(File.expand_path("../application/config/#{enviroment}/#{enviroment}.yml", __FILE__)))

        db_config.each do |key, value|
            if key == "#{enviroment}"
                value.each do |key, db|
                    
                    db_name     = "#{db_local_config['develop'][key]['name']}"
                    db_user     = "#{db_local_config['develop'][key]['user']}"
                    db_host     = "#{db_local_config['develop'][key]['host']}"
                    db_pass     = "#{db_local_config['develop'][key]['pass']}"
                    filename    = "#{db["name"]}.sql"
                    backup_name = "#{db["name"]}_#{timestamp_now}.sql"
                    
                    # check for compressed file and decompress
                    if local_file_exists?("/tmp/#{filename}.gz")
                        logger.debug "Decompressing local dump [/tmp/#{filename}.gz] ..."
                        system("gunzip -f /tmp/#{filename}.gz")
                    end
                    
                    if local_file_exists?("/tmp/#{filename}")
                        
                        # run through replacements on SQL file
                        # db_regex_hash.each_pair do |local, remote|
                        #     system "perl -pi -e 's/#{remote}/#{local}/g' #{filename}"
                        # end

                        # Creat local directory for local backups if not exists
                        system "mkdir -p #{local_db_backup_path}" unless local_dir_exists?("#{local_db_backup_path}")
                        
                        # Make a backup of local database and compress it
                        logger.debug "Dumping local backup database to [#{local_db_backup_path}/#{backup_name}] ..."
                        system "mysqldump --add-drop-table --opt --compress -h #{db_host} -u #{db_user} -p#{db_pass} #{db_name} > #{local_db_backup_path}/#{backup_name}"

                        logger.debug "Compressing local backup database [#{local_db_backup_path}/#{backup_name}.gz] ..."
                        system "gzip -9 #{local_db_backup_path}/#{backup_name}"

                        # Restore database from dump downloaded from server
                        logger.debug "Restoring local database dump file [/tmp/#{filename}] to [#{db_name}] database ..."
                        system "mysql -h #{db_host} -u #{db_user} -p#{db_pass} #{db_name} < /tmp/#{filename}"
                        # Remove dump downloaded from server
                        logger.debug "Removing local file dump from /tmp/#{filename}"
                        system "rm -f /tmp/#{filename}"
                       
                    else
                        logger.debug "Dump file for database [#{db["name"]}] was not found local [/tmp/#{db["name"]}]"
                    end
                end
            end
        end
    end

    desc "Migrate remote application database to local server"
    task :import_db, :roles => :db, :except => { :no_release => true } do
        remote_create_dump
        remote_get_dump
        local_restore_dump
    end
end