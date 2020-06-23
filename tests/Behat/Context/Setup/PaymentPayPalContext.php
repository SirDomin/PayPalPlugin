<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\PayPalPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Sylius\PayPalPlugin\Behat\Page\Shop\Checkout\PayPalSelectPaymentPageInterface;
use Webmozart\Assert\Assert;

final class PaymentPayPalContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var PaymentMethodRepositoryInterface */
    private $paymentMethodRepository;

    /** @var ExampleFactoryInterface */
    private $paymentMethodExampleFactory;

    /** @var array */
    private $gatewayFactories;

    /** @var TranslatorInterface */
    private $translator;

    /** @var PayPalSelectPaymentPageInterface */
    private $paymentPage;

    public function __construct(
        SharedStorageInterface $sharedStorage,
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ExampleFactoryInterface $paymentMethodExampleFactory,
        array $gatewayFactories,
        TranslatorInterface $translator,
        PayPalSelectPaymentPageInterface $paymentPage
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentMethodExampleFactory = $paymentMethodExampleFactory;
        $this->gatewayFactories = $gatewayFactories;
        $this->translator = $translator;
        $this->paymentPage = $paymentPage;
    }

    /**
     * @Given /^the store allows paying with "([^"]*)" with "([^"]*)" factory name at position (\d+)$/
     */
    public function theStoreAllowsPayingWithWithFactoryNameAtPosition(string $paymentMethodName, string $gatewayFactory, int $position)
    {
        $this->createPaymentMethod($paymentMethodName, 'PM_' . $paymentMethodName, $gatewayFactory, 'Payment method', $position);
    }

    /**
     * @Given /^I should have "([^"]*)" payment method selected$/
     */
    public function iShouldHavePaymentMethodSelected(string $paymentMethodName): void
    {
        Assert::true($this->paymentPage->hasPaymentMethodSelected($paymentMethodName));
    }

    private function createPaymentMethod(
        string $name,
        string $code,
        string $gatewayFactory,
        string $description,
        int $position
    ): void {
        $gatewayFactory = $this->findGatewayNameByTranslation($gatewayFactory, $this->gatewayFactories);

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $this->paymentMethodExampleFactory->create([
            'name' => ucfirst($name),
            'code' => $code,
            'description' => $description,
            'gatewayName' => $gatewayFactory,
            'gatewayFactory' => $gatewayFactory,
            'enabled' => true,
            'channels' => ($this->sharedStorage->has('channel')) ? [$this->sharedStorage->get('channel')] : [],
        ]);

        $paymentMethod->setPosition((int) $position);

        $this->sharedStorage->set('payment_method', $paymentMethod);
        $this->paymentMethodRepository->add($paymentMethod);
    }

    private function findGatewayNameByTranslation($translation, $gateways): ?string
    {
        foreach ($gateways as $key => $value) {
            if ($this->translator->trans($value) === $translation) {
                return $key;
            }
        }

        return null;
    }
}