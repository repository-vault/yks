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
    * Add a public key in a customer authorization file
    * @return void
    */
    public static function addUserPubkey($user, $newKey)
    {
      if (!self::isRoot())
        throw new \Exception("You must be root");

        syslog(LOG_INFO, "Adding key to $user : $newKey");
        $path = Sys::getHome($user) . '/.ssh/authorized_keys';
        $dir = dirname($path);

        if (!file_exists($dir)) {
            mkdir($dir);
            chmod($dir, 0700);
            chown($dir, $user);
        }

        $keys = file_exists($path) ? file($path) : [];
        $keys[] = $newKey;
        $keys = array_unique(array_filter(array_map('trim', $keys)));
        sort($keys);

        file_put_contents($path, implode(PHP_EOL, $keys) . PHP_EOL);
        chmod($path, 0600);
        chown($path, $user);
    }

    /**
     * @return bool true if the current user has root privileges.
     */
    public static function isRoot()
    {
      static $isRoot = null;
      if (!is_null($isRoot))
        return $isRoot;
        // Don't use `whoami` as it's not present on all systems (eg. OpenWrt).
      $isRoot = trim(shell_exec('id -u')) === '0';
      return $isRoot;
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

    /**
     * Put the process in the background and return its PID.
     *
     * @see https://stackoverflow.com/a/2036816/985610
     *
     * @return int new PID.
     */
    public static function daemonize()
    {
        $fork = function() {
            $pid = pcntl_fork();
            if ($pid > 0)
                exit(0);
            else if ($pid < 0)
                throw new \RuntimeException('Unable to fork.');
            return $pid;
        };

        $fork();
        posix_setsid();
        $pid = $fork();
        fclose(STDIN);
        fclose(STDERR);
        fclose(STDOUT);

        return $pid;
    }
}
