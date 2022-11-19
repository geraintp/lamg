<?php
namespace Deployer;

require __DIR__ . '/vendor/autoload.php';

require 'recipe/common.php';
require 'recipe/statamic.php';
require 'contrib/php-fpm.php';
require 'contrib/npm.php';
require 'contrib/slack.php';

// Project name
set('application', 'lamg');
set('repository', 'git@github.com:geraintp/lamg.git');
set('php_fpm_version', '8.1');
set('keep_releases', 3);

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 
set('http_user', 'www-data');
set('writable_mode', 'chown');

// Shared files/dirs between deploys 
// add('shared_files', []);
// add('shared_dirs', []);

// Writable dirs by web server 
add('writable_dirs', ['./storage/framework/cache/data/stache/']);
set('allow_anonymous_stats', false);

add('copy_dirs', ['./vendors', './node_modules']);

// Hosts

host('prod')
    ->set('hostname', 'test.amseru.uk')
    ->set('remote_user', 'root')
    ->set('deploy_path', '/var/www/test.amseru.uk');    
    
// Tasks
task('load:env', function() {
    $environment = run('cat {{deploy_path}}/shared/.env');

    $env = \Dotenv\Dotenv::parse($environment);
    var_dump($env['DEPLOY_SLACK_WEB_HOOK']);
    if (array_key_exists('DEPLOY_SLACK_WEB_HOOK', $env)) {
        set('slack_webhook', $env['DEPLOY_SLACK_WEB_HOOK']);
    }
})->desc('Load DotEnv values');
before('slack:notify', 'load:env');


task('nginx:restart', function () {
    run('service nginx restart');
});

task('npm:install', function(){
    run('cd {{release_path}} && {{bin/npm}} ic --no-optional');
});

task('npm:run:prod', function () {
    cd('{{release_path}}');
    run('{{bin/npm}} run production');
});

task('npm:run:dev', function () {
    run('cd {{release_path}} && {{bin/npm}} run dev');
});

task('npm:cache:clean', function(){
    run('cd {{release_path}} && {{bin/npm}} cache clean --force');
});

task('fix:perms', function() {
    cd('{{release_or_current_path}}');
    run('chown -R www-data:www-data .');
    cd('{{release_or_current_path}}/storage');
    run('chown -R www-data:www-data .');
});

desc('Show the content of the app directory.');
task('app:directory', function () {
    cd('{{release_or_current_path}}');
    $output = run('ls -la');
    writeln($output);
});

task('deploy', [
    'deploy:prepare',
    'deploy:copy_dirs',
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    // 'artisan:migrate',
    'npm:install',
    'npm:run:prod',

    'statamic:stache:clear',
    'statamic:stache:warm',
    
    'fix:perms',
    'deploy:publish',
    // 'php-fpm:reload',
]);

before('deploy', 'slack:notify');
// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'slack:notify:failure');
after('deploy:success', 'slack:notify:success');
