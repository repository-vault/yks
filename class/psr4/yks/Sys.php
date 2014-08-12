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

    /**
     * Return the MAC address of the given interface.
     *
     * @param string $iface
     * @return string
     */
    public static function getMac($iface)
    {
        exec('which ip', $_, $exit);
        $hasIp = $exit === 0;
        exec('which ifconfig', $_, $exit);
        $hasIfconfig = $exit === 0;

        /* Some have ip, some have ifconfig, some have both, none have neither,
         * or se we hope. */
        if (!$hasIp && !$hasIfconfig)
            throw new \RuntimeException('Don\'t know how to get MAC address.');

        if ($hasIp)
            $mac = exec("ip link show $iface | grep 'link/ether' | cut -d' ' -f 6");
        else
            $mac = exec("ifconfig $iface | grep HWaddr | cut -d' ' -f 11");

        if (strlen($mac) !== 17)
            throw new \RuntimeException('Unable to get MAC address.');

        return $mac;
    }
}
