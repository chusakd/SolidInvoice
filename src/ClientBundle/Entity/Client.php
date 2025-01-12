<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={"normalization_context"={"groups"={"client_api"}},
 *     "denormalization_context"={"groups"={"client_api"}}},
 *     iri="https://schema.org/Corporation"
 * )
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"name", "company_id"})
 *     },
 *     name="clients"
 * )
 * @ORM\Entity(repositoryClass="SolidInvoice\ClientBundle\Repository\ClientRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("name")
 */
class Client implements Stringable
{
    use Archivable;
    use TimeStampable;
    use CompanyAware;

    /**
     * @ORM\Column(name="id", type="uuid_binary_ordered_time")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidOrderedTimeGenerator::class)
     * @Serialize\Groups({"client_api"})
     */
    private ?UuidInterface $id = null;

    /**
     * @ORM\Column(name="name", type="string", length=125)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="https://schema.org/name")
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="website", type="string", length=125, nullable=true)
     * @Assert\Url()
     * @Assert\Length(max=125)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="https://schema.org/URL")
     */
    private ?string $website = null;

    /**
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="https://schema.org/Text")
     */
    private ?string $status = null;

    /**
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="https://schema.org/Text")
     */
    private ?string $currency = null;

    /**
     * @ORM\Column(name="vat_number", type="string", nullable=true)
     * @Serialize\Groups({"client_api"})
     */
    private ?string $vatNumber = null;

    /**
     * @var Collection<int, Contact>
     *
     * @ORM\OneToMany(targetEntity="Contact", mappedBy="client", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"firstName" = "ASC"})
     * @Assert\Count(min=1, minMessage="You need to add at least one contact to this client")
     * @Assert\Valid()
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="https://schema.org/Person")
     */
    private Collection $contacts;

    /**
     * @var Collection<int, Quote>
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\QuoteBundle\Entity\Quote", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"created" = "DESC"})
     * @ApiSubresource
     */
    private Collection $quotes;

    /**
     * @var Collection<int, Invoice>
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\InvoiceBundle\Entity\Invoice", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"created" = "DESC"})
     * @ApiSubresource
     */
    private Collection $invoices;

    /**
     * @var Collection<int, RecurringInvoice>
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\InvoiceBundle\Entity\RecurringInvoice", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"created" = "DESC"})
     * @ApiSubresource
     */
    private Collection $recurringInvoices;

    /**
     * @var Collection<int, Payment>
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\PaymentBundle\Entity\Payment", mappedBy="client", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ApiSubresource
     */
    private Collection $payments;

    /**
     * @var Collection<int, Address>
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\ClientBundle\Entity\Address", mappedBy="client", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Serialize\Groups({"client_api"})
     */
    private Collection $addresses;

    /**
     * @ORM\OneToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Credit", mappedBy="client", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="https://schema.org/MonetaryAmount")
     */
    private ?Credit $credit = null;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->quotes = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->recurringInvoices = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->addresses = new ArrayCollection();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function addContact(Contact $contact): self
    {
        $this->contacts[] = $contact;
        $contact->setClient($this);

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addQuote(Quote $quote): self
    {
        $this->quotes[] = $quote;
        $quote->setClient($this);

        return $this;
    }

    public function removeQuote(Quote $quote): self
    {
        $this->quotes->removeElement($quote);

        return $this;
    }

    /**
     * @return Collection<int, Quote>
     */
    public function getQuotes(): Collection
    {
        return $this->quotes;
    }

    public function addInvoice(Invoice $invoice): self
    {
        $this->invoices[] = $invoice;
        $invoice->setClient($this);

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self
    {
        $this->invoices->removeElement($invoice);

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addRecurringInvoice(RecurringInvoice $invoice): self
    {
        $this->recurringInvoices[] = $invoice;
        $invoice->setClient($this);

        return $this;
    }

    public function removeRecurringInvoice(RecurringInvoice $invoice): self
    {
        $this->recurringInvoices->removeElement($invoice);

        return $this;
    }

    /**
     * @return Collection<int, RecurringInvoice>
     */
    public function getRecurringInvoices(): Collection
    {
        return $this->recurringInvoices;
    }

    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;
        $payment->setClient($this);

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addAddress(?Address $address): self
    {
        if ($address instanceof Address) {
            $this->addresses[] = $address;
            $address->setClient($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function getCredit(): ?Credit
    {
        return $this->credit;
    }

    public function setCredit(Credit $credit): self
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setInitialCredit(): void
    {
        if (! $this->id instanceof UuidInterface) {
            $credit = new Credit();
            $credit->setClient($this);
            $this->setCredit($credit);
        }
    }

    public function getCurrency(): ?Currency
    {
        return null !== $this->currency ? new Currency($this->currency) : null;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): self
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
