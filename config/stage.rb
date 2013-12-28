
desc "Run tasks in staging enviroment."
task :staging do
    # Staging nodes
    set :branch,           "develop"
    set :deploy_to,        "#{app_config['config']['shared_path']}#{application}/staging"
    set :backups_path,     File.join(deploy_to, "backups")
    set :tmp_backups_path, File.join("#{backups_path}", "tmp/")
    set :enviroment,       'staging'
    _cset(:backups)        { capture("ls -x #{backups_path}", :except => { :no_release => true }).split.sort }

end

#after 'deploy:update_code', 'deploy:symlink_shared', 'deploy:create_storage', 'deploy:setconfig', 'deploy:backupdb', 'deploy:bkpfiles', 'deploy:cleanup'
after 'deploy:update_code', 'deploy:symlink_shared', 'deploy:create_storage', 'deploy:setconfig', 'deploy:cleanup'
