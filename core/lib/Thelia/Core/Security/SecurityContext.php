<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Core\Security;

use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\User\UserInterface;
use Thelia\Core\HttpFoundation\Request;

/**
 * A simple security manager, in charge of checking user
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class SecurityContext
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    private function getSession()
    {
        $session = $this->request->getSession();

        if ($session === null) {
            throw new \LogicException("No session found.");
        }

        return $session;
    }

    /**
    * Gets the currently authenticated user in  the admin, or null if none is defined
    *
    * @return UserInterface|null A UserInterface instance or null if no user is available
    */
    public function getAdminUser()
    {
        return $this->getSession()->getAdminUser();
    }

    /**
     * Check if an admin user is logged in.
     *
     * @return true if an admin user is logged in, false otherwise.
     */
    public function hasAdminUser()
    {
        return $this->getSession()->getAdminUser() !== null;
    }

    /**
     * Gets the currently authenticated customer, or null if none is defined
     *
     * @return UserInterface|null A UserInterface instance or null if no user is available
     */
    public function getCustomerUser()
    {
        return $this->getSession()->getCustomerUser();
    }

    /**
     * Check if a customer user is logged in.
     *
     * @return true if a customer is logged in, false otherwise.
     */
    public function hasCustomerUser()
    {
        return $this->getSession()->getCustomerUser() !== null;
    }

    /**
     * Check if a user has at least one of the required roles
     *
     * @param  UserInterface $user  the user
     * @param  array         $roles the roles
     * @return boolean       true if the user has the required role, false otherwise
     */
    final public function hasRequiredRole(UserInterface $user = null, array $roles)
    {
        if ($user != null) {
            // Check if user's roles matches required roles
            $userRoles = $user->getRoles();

            foreach ($userRoles as $role) {
                if (in_array($role, $roles)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
    * Checks if the current user is allowed
    *
    * @return Boolean
    */
    final public function isGranted(array $roles, array $resources, array $modules, array $accesses)
    {
        // Find a user which matches the required roles.
        $user = $this->getCustomerUser();

        if (! $this->hasRequiredRole($user, $roles)) {
            $user = $this->getAdminUser();

            if (! $this->hasRequiredRole($user, $roles)) {
                $user = null;
            }
        }

        if (null === $user) {
            return false;
        }

        if ((empty($resources) && empty($modules)) || empty($accesses)) {
            return true;
        }

        if (!method_exists($user, 'getPermissions')) {
            return false;
        }

        $userPermissions = $user->getPermissions();

        if ($userPermissions === AdminResources::SUPERADMINISTRATOR) {
            return true;
        }

        foreach ($resources as $resource) {
            if ($resource === '') {
                continue;
            }

            $resource = strtolower($resource);

            if (!array_key_exists($resource, $userPermissions)) {
                return false;
            }

            foreach ($accesses as $access) {
                if (!$userPermissions[$resource]->can($access)) {
                    return false;
                }
            }
        }

        foreach ($modules as $module) {
            if ($module === '') {
                continue;
            }

            if (!array_key_exists('module', $userPermissions)) {
                return false;
            }

            $module = strtolower($module);

            if (!array_key_exists($module, $userPermissions['module'])) {
                return false;
            }

            foreach ($accesses as $access) {
                if (!$userPermissions['module'][$module]->can($access)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
    * Sets the authenticated admin user.
    *
    * @param UserInterface $user A UserInterface, or null if no further user should be stored
    */
    public function setAdminUser(UserInterface $user)
    {
        $user->eraseCredentials();

        $this->getSession()->setAdminUser($user);
    }

    /**
     * Sets the authenticated customer user.
     *
     * @param UserInterface $user A UserInterface, or null if no further user should be stored
     */
    public function setCustomerUser(UserInterface $user)
    {
        $user->eraseCredentials();

        $this->getSession()->setCustomerUser($user);
    }

    /**
     * Clear the customer from the security context
     */
    public function clearCustomerUser()
    {
        $this->getSession()->clearCustomerUser();
    }

    /**
     * Clear the admin from the security context
     */
    public function clearAdminUser()
    {
        $this->getSession()->clearAdminUser();
    }
}
