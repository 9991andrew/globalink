<?php

use PHPUnit\Framework\TestCase;

/**
 * Class PlayerTest
 * These tests typically should be run through from first to last in order to ensure that the players are created,
 * tested, and deleted.
 *
 * These tests depend on the UserTest tests passing.
 *
 * These tests will fail if any of the test player names are already used in the database, of course.
 * If that is legitimately the case, you may change these users in the dataProvider methods
 */
class PlayerTest extends TestCase
{

    /**
     * User should be able to create a username with unique names.
     * Password length validation is done completely on the front-end (so far).
     * @test
     * @dataProvider userCredentials
     */
    public function it_creates_a_new_player(string $username, string $password, string $playerName, int $birthplaceId, int $raceId, int $genderId, array $attributeValues)
    {
        // Ensure the player isn't already in the database.
        Player::delete($playerName);

        // Ensure the user isn't already in the database (this will delete all the user's players)
        User::delete($username);

        // Creates a completely new user for each new player
        $user = User::create($username, $password);
        $player = Player::create($user->getId(), $playerName, $birthplaceId, $raceId, $genderId, $attributeValues);


        $this->assertEquals('Player', get_class($player));
        $this->assertEquals($playerName, $player->getName());

        // Check that we get this new player when we check the players this user has
        $this->assertContains($player->getId(), $user->getPlayerIds());

    }

    /**
     * @test
     * @dataProvider userCredentials
     */
    public function it_allows_creating_player_object_with_existing_id(string $username, string $password, string $playerName)
    {
        $user = new User($username);
        $playerId = Player::getPlayerIdWithName($playerName);
        $player = new Player($playerId);
        $this->assertEquals($playerName, $player->getName());
        $this->assertEquals($user->getId(), $player->getUserId());
    }


    /**
     * Test Deleting the player to clean up
     * @test
     * @dataProvider userCredentials
     */
    public function it_can_delete_the_user(string $username, string $password, string $playerName, $birthplaceId, $raceId, $genderId, $attributeValues)
    {

        // Ensure the player and user aren't already in the database.
        Player::delete($playerName);
        User::delete($username);

        $user = User::create($username, $password);
        $player = Player::create($user->getId(), $playerName, $birthplaceId, $raceId, $genderId, $attributeValues);
        $playerId = $player->getId();
        Player::delete($player->getId());
        $this->expectException(InvalidPlayerException::class);
        $player = new Player($playerId);
        echo "Error: User '".$player->getName()."' still exists!";
    }


    // dataProvider methods
    /**
     * @return string[][]
     */
    public function userCredentials(): array
    {
        return [
            //username, password, playername, birthplaceId, raceId, genderId, attributeValues
            ['testUser2000', '1234asdf;lkj', 'testPlayerName1', 1, 1, 1, [1,2,3,4,5,6]],
            ['testUser2001', '1234asdf;lkj', 'testPlayerName2', 2, 2, 2, [6,5,4,3,2,1]],
        ];
    }



}
