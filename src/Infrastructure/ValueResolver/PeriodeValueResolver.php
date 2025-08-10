<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueResolver;

use App\Domain\Temps\Depuis;
use App\Domain\Temps\Periode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class PeriodeValueResolver implements ValueResolverInterface
{
    /** @return array<Periode> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Periode::class !== $argument->getType()) {
            return [];
        }

        if ($request->get('date_filter')) {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateEndString 23:59:59");

            if (!($dateStart instanceof \DateTimeImmutable) || !($dateEnd instanceof \DateTimeImmutable) || $dateStart > $dateEnd) {
                throw new BadRequestHttpException('La période de dates est invalide.');
            }

            return [new Periode($dateStart, $dateEnd)];
        }

        $attributes = $argument->getAttributes(PeriodeParDefautAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

        if ($attributes) {
            /** @var PeriodeParDefautAttribute $attribute */
            $attribute = $attributes[0];

            return [
                match ($attribute->depuis) {
                    Depuis::UN_AN => $this->depuisUnAn(),
                    Depuis::UN_MOIS => $this->depuisUnMois(),
                },
            ];
        }

        return [$this->depuisUnMois()];
    }

    /**
     * Depuis un an et jusqu'à la fin du mois.
     */
    public function depuisUnAn(): Periode
    {
        list($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

        $month = (int) $month;
        $year = (int) $year;
        $lastDayOfMonth = (int) $lastDayOfMonth;

        $dateStart = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
        if ($dateStart instanceof \DateTimeImmutable) {
            $dateStart = $dateStart->modify('-1 year')->setTime(0, 0); // Depuis un an
        }
        $dateEnd = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");

        if (!($dateStart instanceof \DateTimeImmutable) || !($dateEnd instanceof \DateTimeImmutable) || $dateStart > $dateEnd) {
            throw new BadRequestHttpException('La période de dates est invalide.');
        }

        return new Periode($dateStart, $dateEnd);
    }

    /**
     * Le mois courant en entier.
     */
    public function depuisUnMois(): Periode
    {
        list($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

        $month = (int) $month;
        $year = (int) $year;
        $lastDayOfMonth = (int) $lastDayOfMonth;

        $dateStart = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
        $dateEnd = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");

        if (!($dateStart instanceof \DateTimeImmutable) || !($dateEnd instanceof \DateTimeImmutable) || $dateStart > $dateEnd) {
            throw new BadRequestHttpException('La période de dates est invalide.');
        }

        return new Periode($dateStart, $dateEnd);
    }
}
