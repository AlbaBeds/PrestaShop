<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShopBundle\Controller\Admin\Configure\AdvancedParameters\AuthorizationServer;

use Exception;
use PrestaShop\PrestaShop\Core\Domain\ApiAccess\ApiAccessSettings;
use PrestaShop\PrestaShop\Core\Domain\ApiAccess\Exception\ApiAccessConstraintException;
use PrestaShop\PrestaShop\Core\Domain\ApiAccess\Exception\ApiAccessNotFoundException;
use PrestaShop\PrestaShop\Core\Domain\ApiAccess\Exception\CannotAddApiAccessException;
use PrestaShop\PrestaShop\Core\Domain\ApiAccess\Exception\CannotUpdateApiAccessException;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Builder\FormBuilderInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Handler\FormHandlerInterface;
use PrestaShop\PrestaShop\Core\Search\Filters\ApiAccessFilters;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manages the "Configure > Advanced Parameters > Authorization Server > Api Access" page.
 *
 * @experimental
 */
class ApiAccessController extends FrameworkBundleAdminController
{
    /**
     * @AdminSecurity("is_granted('create', request.get('_legacy_controller')) || is_granted('update', request.get('_legacy_controller')) || is_granted('delete', request.get('_legacy_controller')) || is_granted('read', request.get('_legacy_controller'))")
     *
     * @return Response
     */
    public function indexAction(ApiAccessFilters $apiAccessesFilters): Response
    {
        $apiAccessGridFactory = $this->get('prestashop.core.grid.factory.api_access');
        $apiAccessGrid = $apiAccessGridFactory->getGrid($apiAccessesFilters);

        return $this->render('@PrestaShop/Admin/Configure/AdvancedParameters/AuthorizationServer/ApiAccess/index.html.twig', [
            'apiAccessGrid' => $this->presentGrid($apiAccessGrid),
            'help_link' => $this->generateSidebarLink('AdminAuthorizationServer'),
            'layoutTitle' => $this->trans('API Access list', 'Admin.Navigation.Menu'),
            'layoutHeaderToolbarBtn' => $this->getApiAccessesToolbarButtons(),
        ]);
    }

    /**
     * @AdminSecurity("is_granted('create', request.get('_legacy_controller'))")
     */
    public function createAction(Request $request): Response
    {
        $apiAccessForm = $this->getFormBuilder()->getForm();
        $apiAccessForm->handleRequest($request);

        try {
            $handlerResult = $this->getFormHandler()->handle($apiAccessForm);

            if (null !== $handlerResult->getIdentifiableObjectId()) {
                $this->addFlash('success', $this->trans('Successful creation', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_api_accesses_edit', ['apiAccessId' => $handlerResult->getIdentifiableObjectId()]);
            }
        } catch (Exception $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }

        return $this->render(
            '@PrestaShop/Admin/Configure/AdvancedParameters/AuthorizationServer/ApiAccess/create.html.twig',
            [
                'layoutTitle' => $this->trans('New API Access', 'Admin.Navigation.Menu'),
                'apiAccessForm' => $apiAccessForm->createView(),
            ]
        );
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     */
    public function editAction(Request $request, int $apiAccessId): Response
    {
        $apiAccessForm = $this->getFormBuilder()->getFormFor($apiAccessId);
        $apiAccessForm->handleRequest($request);

        try {
            $handlerResult = $this->getFormHandler()->handleFor($apiAccessId, $apiAccessForm);

            if (null !== $handlerResult->getIdentifiableObjectId()) {
                $this->addFlash('success', $this->trans('Successful update', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_api_accesses_edit', ['apiAccessId' => $apiAccessId]);
            }
        } catch (Exception $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }

        $formData = $apiAccessForm->getData();

        return $this->render('@PrestaShop/Admin/Configure/AdvancedParameters/AuthorizationServer/ApiAccess/edit.html.twig', [
            'layoutTitle' => $this->trans('Editing API Access "%name%"', 'Admin.Navigation.Menu', ['%name%' => $formData['client_name']]),
            'apiAccessForm' => $apiAccessForm->createView(),
        ]);
    }

    private function getFormHandler(): FormHandlerInterface
    {
        return $this->get('prestashop.core.form.identifiable_object.api_access_form_handler');
    }

    private function getFormBuilder(): FormBuilderInterface
    {
        return $this->get('prestashop.core.form.identifiable_object.builder.api_access_form_builder');
    }

    /**
     * @return array
     */
    private function getApiAccessesToolbarButtons(): array
    {
        $toolbarButtons = [];

        $toolbarButtons['addApiAccess'] = [
            'href' => $this->generateUrl('admin_api_accesses_create'),
            'desc' => $this->trans('Add new API Access', 'Admin.Actions'),
            'icon' => 'add_circle_outline',
            'class' => 'btn-primary',
        ];

        return $toolbarButtons;
    }

    /**
     * Provides translated error messages for exceptions
     *
     * @return array
     */
    private function getErrorMessages()
    {
        return [
            ApiAccessNotFoundException::class => $this->trans(
                'The object cannot be loaded (or found).',
                'Admin.Notifications.Error'
            ),
            ApiAccessConstraintException::class => [
                ApiAccessConstraintException::CLIENT_ID_ALREADY_USED => $this->trans(
                    'This value for "%field%" is already used and must be unique.',
                    'Admin.Notifications.Error',
                    ['%field%' => $this->trans('Client ID', 'Admin.Advparameters.Feature')]
                ),
                ApiAccessConstraintException::CLIENT_NAME_ALREADY_USED => $this->trans(
                    'This value for "%field%" is already used and must be unique.',
                    'Admin.Notifications.Error',
                    ['%field%' => $this->trans('Client Name', 'Admin.Advparameters.Feature')]
                ),
                ApiAccessConstraintException::INVALID_CLIENT_ID => $this->trans(
                    'The %s field is invalid.',
                    'Admin.Notifications.Error',
                    [sprintf('"%s"', $this->trans('Client ID', 'Admin.Advparameters.Feature'))]
                ),
                ApiAccessConstraintException::INVALID_CLIENT_NAME => $this->trans(
                    'The %s field is invalid.',
                    'Admin.Notifications.Error',
                    [sprintf('"%s"', $this->trans('Client Name', 'Admin.Advparameters.Feature'))]
                ),
                ApiAccessConstraintException::INVALID_DESCRIPTION => $this->trans(
                    'The %s field is invalid.',
                    'Admin.Notifications.Error',
                    [sprintf('"%s"', $this->trans('Description', 'Admin.Global'))]
                ),
                ApiAccessConstraintException::CLIENT_ID_TOO_LARGE => $this->trans(
                    'The field "%field%" cannot be longer than %limit% characters.',
                    'Admin.Notifications.Error',
                    [
                        '%field%' => $this->trans('Client ID', 'Admin.Advparameters.Feature'),
                        '%limit%' => ApiAccessSettings::MAX_CLIENT_ID_LENGTH,
                    ]
                ),
                ApiAccessConstraintException::CLIENT_NAME_TOO_LARGE => $this->trans(
                    'The field "%field%" cannot be longer than %limit% characters.',
                    'Admin.Notifications.Error',
                    [
                        '%field%' => $this->trans('Client Name', 'Admin.Advparameters.Feature'),
                        '%limit%' => ApiAccessSettings::MAX_CLIENT_NAME_LENGTH,
                    ]
                ),
                ApiAccessConstraintException::DESCRIPTION_TOO_LARGE => $this->trans(
                    'The field "%field%" cannot be longer than %limit% characters.',
                    'Admin.Notifications.Error',
                    [
                        '%field%' => $this->trans('Description', 'Admin.Global'),
                        '%limit%' => ApiAccessSettings::MAX_DESCRIPTION_LENGTH,
                    ]
                ),
            ],
            CannotAddApiAccessException::class => $this->trans(
                'An error occurred while creating the API Access.',
                'Admin.Advparameters.Notification'
            ),
            CannotUpdateApiAccessException::class => $this->trans(
                'An error occurred while creating the API Access.',
                'Admin.Advparameters.Notification'
            ),
        ];
    }
}
