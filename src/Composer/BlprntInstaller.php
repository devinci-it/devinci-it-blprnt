<?php

namespace DevinciIT\Blprnt\Composer;

use DevinciIT\Blprnt\Core\Installer;
use DevinciIT\Blprnt\Console\Commands\Styles\BuildStylesCommand;

class BlprntInstaller extends Installer
{
    /**
     * Main installation logic
     */
    protected function install(): void
    {
        $this->io()->info('Installing Blprnt skeleton...');

        /*
        |--------------------------------------------------------------------------
        | Core structure
        |--------------------------------------------------------------------------
        */
        $this->io()->info('Publishing core structure...');

        $this->dir('resources/skel/bootstrap', 'bootstrap');
        $this->dir('resources/skel/routes', 'routes');
        $this->dir('resources/skel/config', 'config');

        $this->io()->success('Core structure published.');

        /*
        |--------------------------------------------------------------------------
        | Base files
        |--------------------------------------------------------------------------
        */
        $this->file('resources/skel/.env.tmp', '.env');
        $this->file('blprnt', 'blprnt');

        /*
        |--------------------------------------------------------------------------
        | App directory
        |--------------------------------------------------------------------------
        */
        $this->io->hr();
        $this->io()->accent('Publishing Assets...');
    
        $this->io()->info('Publishing app directory structure...');
        $this->dir('resources/skel/app/Controllers', 'app/Controllers');
        $this->dir('resources/skel/app/Views/auth', 'app/Views/auth');
        $this->dir('resources/skel/app/Views/errors', 'app/Views/errors');
        $this->dir('resources/skel/app/Views/layouts', 'app/Views/layouts');

        $this->io->hr();

        /*
        |--------------------------------------------------------------------------
        | Resources + Public
        |--------------------------------------------------------------------------
        */
        $this->dir('resources/skel/public', 'public');
        $this->buildCss();

        /*
        |--------------------------------------------------------------------------
        | Public assets
        |--------------------------------------------------------------------------
        */
        $this->copy('resources/logo.svg', 'public/logo.svg');
        $this->copy('resources/favicon.svg', 'public/favicon.svg');
        $this->copy('resources/img/graphics.svg', 'public/graphics.svg');
    }

    /*
    |--------------------------------------------------------------------------
    | Build CSS assets
    |--------------------------------------------------------------------------
    */
    protected function buildCss(): void
    {
        $command = new BuildStylesCommand();

        $command->run([
            '--source=vendor/devinci-it/blprnt/resources/scss',
            '--output=public/vendor/devinci-it/blprnt/css',
        ]);
    }

    /**
     * Lifecycle hook: before install
     */
    protected function before(): void
    {
        $this->io()->info('Starting Blprnt installation...');
    }

    /**
     * Lifecycle hook: after install
     */
    protected function after(): void
    {
        $this->io()->success('Blprnt installed successfully 🚀');
    }
}
