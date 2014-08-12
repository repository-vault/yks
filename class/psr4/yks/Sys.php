<?php

namespace yks;

/**
 * Lowish level methods for querying the OS.
 */
class Sys
{
    /**
     * Return the path to the home directory of the given user.
     *
     * @param $user int|string user name or UID.
     */
    public static function getHome($user)
    {
        $cmd = 'getent passwd %s | cut -d: -f6';
        $home = exec(sprintf($cmd, escapeshellarg($user)), $_, $exit);
        if ($exit !== 0 || strlen($home) <= 0)
            throw new \InvalidArgumentException("Invalid user `$user`, home not found.");

        return $home;
    }

    /**
     * @return bool true if the current user has root privileges.
     */
    public static function isRoot()
    {
        // Don't use `whoami` as it's not present on all systems (eg. OpenWrt).
        return trim(shell_exec('id -u')) === '0';
    }
}
