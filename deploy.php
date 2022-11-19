<?php
namespace Deployer;

require 'recipe/statamic.php';
require 'contrib/php-fpm.php';
require 'contrib/npm.php';
require 'contrib/slack.php';

// Project name
set('application', 'lamg');
set('repository', 'git@github.com:geraintp/lamg.git');
set('php_fpm_version', '8.1');
set('slack_webhook', 'https://hooks.slack.com/services/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');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
// add('shared_files', []);
// add('shared_dirs', []);

// Writable dirs by web server 
// add('writable_dirs', []);
set('allow_anonymous_stats', false);

// Hosts

host('prod')
    ->set('hostname', 'test.amseru.uk')
    ->set('remote_user', 'root')
    ->set('deploy_path', '/var/www/test.amseru.uk');    
    
// Tasks

task('nginx:restart', function () {
    run('service nginx restart');
});

task('npm:run:prod', function () {
    cd('{{release_path}}');
    run('{{bin/npm}} run production');
});

task('npm:run:dev', function () {
    run('cd {{release_path}} && {{bin/npm}} run dev');
});

desc('Show the content of the app directory.');
task('app:directory', function () {
    cd('{{release_or_current_path}}');
    $output = run('ls -la');
    writeln($output);
});

task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    // 'artisan:migrate',
    'npm:install',
    'npm:run:prod',

    'statamic:stache:clear',
    'statamic:stache:warm',
    'deploy:publish',
    // 'php-fpm:reload',
]);

before('deploy', 'slack:notify');
// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'slack:notify:failure');
after('deploy:success', 'slack:notify:success');
