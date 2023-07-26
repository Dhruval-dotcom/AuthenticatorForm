<?php

namespace App\DataFixtures;

use App\Factory\QuestionFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);


        UserFactory::createOne([
            'email'=>'d@gmail.com',
            'password'=>'et',
            'roles' => ['ROLE_ADMIN']
        ]);
        UserFactory::createOne([
            'email'=>'duser@gmail.com'
        ]);
        UserFactory::createMany(10);

        QuestionFactory::createMany(15, function(){
            return [
                'owner' => UserFactory::random(),
            ];
        });

        $manager->flush();


    }
}
