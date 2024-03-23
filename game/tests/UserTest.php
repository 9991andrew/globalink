<?php

use PHPUnit\Framework\TestCase;

/**
 * Class UserTest
 * These tests typically should be run through from first to last in order to ensure that the users are created,
 * tested, and deleted.
 * These tests will fail if any of the test usernames are already used in the database, of course.
 * If that is legitimately the case, you may change these users in the dataProvider methods
 */
class UserTest extends TestCase
{

    /**
     * User should be able to create a username with unique names.
     * Password length validation is done completely on the front-end (so far).
     * @test
     * @dataProvider userCredentials
     */
    public function it_creates_a_new_user_when_given_unique_username_and_valid_password($username, $password)
    {
        // Ensure the user isn't already in the database (this will delete all the user's players)
        User::delete($username);

        $user = User::create($username, $password);
        $this->assertEquals('User', get_class($user));
        $this->assertEquals($username, $user->getUsername());

    }

    /**
     * @test
     * @dataProvider userCredentials
     */
    public function it_allows_creating_user_object_with_correct_username_and_password_hash($username, $password)
    {
        $user = new User($username, sha1($password));
        $this->assertEquals('User', get_class($user));
        $this->assertEquals($username, $user->getUserName());
    }

    /**
     * We want to ensure a user can't create a username that already exists, even if the case is slightly different
     * @test
     * @dataProvider userCredentials
     */
    public function it_does_not_create_user_when_given_existing_user_name_with_different_case($username, $password)
    {
        $this->expectException(PDOException::class);
        User::create(strtolower($username), $password);
    }

    /**
     * Ensure a user can't log in with an incorrect password.
     * @test
     * @dataProvider userCredentialsBadPasswords
     */
    public function it_does_not_create_user_when_given_incorrect_password($username, $wrongPassword)
    {
        $this->expectException(InvalidPasswordException::class);
        $user = new User(strtolower($username), $wrongPassword);
    }

    /**
     * Throw the correct type of error if a user account that does not exist is tried.
     * @test
     * @dataProvider userCredentialsDoNotExist
     */
    public function it_throws_exception_when_invalid_username_submitted($username, $wrongPassword)
    {
        $this->expectException(InvalidUserException::class);
        $user = new User(strtolower($username), $wrongPassword);
    }

    /**
     * In this case, there is no integrity checking on setting the last player_id,
     * this is all handled by the Player function
     * @test
     * @dataProvider userCredentials
     */
    public function it_can_set_and_get_last_player_id($username, $password)
    {
        $user = new User($username, sha1($password));
        // This test only applies if this is a new user
        $this->assertEquals(null, $user->getLastPlayerID());
        $lastPlayerID = 200;
        // Just set arbitrary number here unless we start verifying the user owns this player
        $user->setLastPlayerID($lastPlayerID);
        // Check that the player property gets updated.
        $this->assertEquals($lastPlayerID, $user->getLastPlayerID());

        // Recreate the object from the DB and ensure the value still matches
        $user = new User($username, sha1($password));
        $this->assertEquals($lastPlayerID, $user->getLastPlayerID());
    }

    /**
     * This should return an array of all the players owned by this user.
     * @test
     * @dataProvider userCredentials
     */
    public function it_can_get_array_of_own_players($username, $password)
    {
        $user = new User($username, sha1($password));
        $this->assertContainsOnly('integer', $user->getPlayerIDs());
    }

    /**
     * TODO: Confirm that the correct ID is being returned
     * @test
     * @dataProvider userCredentials
     */
    public function it_can_get_its_user_id($username, $password)
    {
        $user = new User($username, sha1($password));
        $this->assertIsInt($user->getId());
    }

    /**
     * At this point just make sure the function runs.
     * TODO: In the future the following could be checked:
     *  - User's players are not visible
     *  - Logout has been logged
     *  - Session has been destroyed.
     * @test
     * @dataProvider userCredentials
     */
    public function it_can_logout($username, $password)
    {
        $now = date("Y-m-d H:i:s");
        $user = new User($username, sha1($password));
        $userId = $user->getId();
        $user->logout();
        // Ensure all this user's players are invisible
        foreach($user->getPlayerIDs() as $playerID) {
            $player = new Player($playerID);
            $this->assertEquals('false', $player->getVisible());
        }
        // This is useless, just checks that nothing blew up.
        $this->assertIsInt($user->getId());
    }

    /**
     * Can change the password (and change it back to the original)
     * @test
     * @dataProvider userCredentials
     */
    public function it_can_change_the_password($username, $password)
    {
        $newPassword = 'abcd1234aaa**';
        $user = new User($username, sha1($password));
        $userId = $user->getId();
        $user->changePassword($newPassword);
        // This won't work, I'm not planning to make a getPasswordHash function
        // $this->assertEquals(sha1($newPassword), sha1($user->getPasswordHash()));
        // Ensure the user can log in with the new password
        $user = new User($username, sha1($newPassword));
        $this->assertEquals($userId, $user->getId());

        // Change the password back so we can continue testing with the original passwords.
        // Of course, a very restrictive password policy may not allow re-use in the future
        $user->changePassword($password);
        $user = new User($username, sha1($password));
        $this->assertEquals($userId, $user->getId());
    }

    /**
     * Delete the user to clean up
     * @test
     * @dataProvider userCredentials
     */
    public function it_can_delete_the_user($username, $password)
    {
        User::delete($username);
        $this->expectException(InvalidUserException::class);
        $user = new User($username, sha1($password));
        echo "Error: User '".$user->getUserName()."' still exists!";
    }


    // dataProvider methods
    /**
     * @return string[][]
     */
    public function userCredentials()
    {
        return [
            ['testUser1000', '1234asdf;lkj'],
            ['testUser1001', '1234asd'],
            // Test one with a very long password
            ['testUser1002', '1234asdaasdfasdfasdfasdfasdfasdfasdfasdfasdf'],
        ];
    }
    public function userCredentialsBadPasswords()
    {
        return [
            ['testUser1000', '1234asdf;lkasdf'],
            ['testUser1001', '1234asd'],
            ['testUser1002', '1234asdaasdfasdfasdfasdfasdfasdfasdfasdfasd']
        ];
    }

    public function userCredentialsDoNotExist()
    {
        return [
            ['testUser9999', '1234asdf;lkasdf'],
            ['testUser9998', '1234asd']
        ];
    }

}
