<?php

/**
 * Execute a command in a separate process and return its PID.
 *
 * This function is non-blocking.
 *
 * @param string $cmd command full path.
 * @param string[] $args command arguments.
 * @param string[] $envs environment vars to give to the command, name => value.
 * @return int PID of the child command.
 */
function pcntl_forkexec($cmd, array $args = [], array $envs = [])
{
    $pid = pcntl_fork();
    switch($pid) {
    case -1:
        throw new \RuntimeException('Unable to fork.');
    case 0:
        pcntl_exec($cmd, $args, $envs);
        throw new \RuntimeException("Untable to run `$cmd`.");
    default:
        return $pid;
    }
}

/**
 * Send a signal to a PID and wait for it to complete.
 *
 * This command may be blocking if the target PID does not handle the given
 * signal correctly.
 *
 * @param int $pid PID to target.
 * @param int $signal signal number to send.
 */
function pcntl_killwait($pid, $signal)
{
    posix_kill($pid, $signal);
    pcntl_waitpid($pid, $_);
}
