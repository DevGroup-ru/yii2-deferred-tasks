This is a copy of duncan3dc/fork-helper package.
The reason of copying is that package requires pcntl and shmop that are not required for yii2-deferred-tasks.
If these extensions are not available deferred tasks will run in a plain loop.

