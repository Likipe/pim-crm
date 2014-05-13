<?php

namespace Pim\Bundle\CatalogBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\GridBundle\Action\MassAction\MassActionParametersParser;
use Oro\Bundle\GridBundle\Datagrid\ParametersInterface;
use Oro\Bundle\GridBundle\Action\MassAction\MassActionResponse;

use Pim\Bundle\CatalogBundle\Form\Type\MassEditActionOperatorType;
use Pim\Bundle\CatalogBundle\AbstractController\AbstractDoctrineController;
use Pim\Bundle\CatalogBundle\MassEditAction\MassEditActionOperator;
use Pim\Bundle\GridBundle\Helper\DatagridHelperInterface;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;

/**
 * Batch operation controller
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MassEditActionController extends AbstractDoctrineController
{
    /**
     * @var MassEditActionOperator
     */
    protected $batchOperator;

    /**
     * @var DatagridHelperInterface
     */
    private $datagridHelper;

    /**
     * @var MassActionParametersParser
     */
    private $parametersParser;

    /**
     * @var ProductManager
     */
    private $productManager;

    /**
     * Constructor
     *
     * @param Request                    $request
     * @param EngineInterface            $templating
     * @param RouterInterface            $router
     * @param SecurityContextInterface   $securityContext
     * @param FormFactoryInterface       $formFactory
     * @param ValidatorInterface         $validator
     * @param TranslatorInterface        $translator
     * @param RegistryInterface          $doctrine
     * @param MassEditActionOperator     $batchOperator
     * @param DatagridHelperInterface    $datagridHelper
     * @param MassActionParametersParser $parametersParser
     * @param ProductManager             $productManager
     */
    public function __construct(
        Request $request,
        EngineInterface $templating,
        RouterInterface $router,
        SecurityContextInterface $securityContext,
        FormFactoryInterface $formFactory,
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        RegistryInterface $doctrine,
        MassEditActionOperator $batchOperator,
        DatagridHelperInterface $datagridHelper,
        MassActionParametersParser $parametersParser,
        ProductManager $productManager
    ) {
        parent::__construct(
            $request,
            $templating,
            $router,
            $securityContext,
            $formFactory,
            $validator,
            $translator,
            $doctrine
        );

        $this->batchOperator    = $batchOperator;
        $this->datagridHelper   = $datagridHelper;
        $this->parametersParser = $parametersParser;
        $this->productManager   = $productManager;
    }

    /**
     * @param Request $request
     *
     * @Template
     * @AclAncestor("pim_catalog_mass_edit_choose")
     * @return template|RedirectResponse
     */
    public function chooseAction(Request $request)
    {
        $productIds = $this->getProductIds($request);
        if (!$productIds) {
            return $this->redirectToRoute('pim_catalog_product_index');
        }

        $form = $this->getMassEditActionOperatorForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                return $this->redirectToRoute(
                    'pim_catalog_mass_edit_action_configure',
                    array(
                        'products'       => $productIds,
                        'operationAlias' => $this->batchOperator->getOperationAlias(),
                    )
                );
            }
        }

        return array(
            'form'       => $form->createView(),
            'productIds' => $productIds,
        );
    }

    /**
     * @param Request $request
     * @param string  $operationAlias
     *
     * @AclAncestor("pim_catalog_mass_edit_configure")
     * @throws NotFoundHttpException
     * @return template|RedirectResponse
     */
    public function configureAction(Request $request, $operationAlias)
    {
        try {
            $this->batchOperator->setOperationAlias($operationAlias);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage(), $e);
        }

        $productIds = $this->getProductIds($request);
        if (!$productIds) {
            return $this->redirectToRoute('pim_catalog_product_index');
        }

        $this->batchOperator->initializeOperation($productIds);
        $form = $this->getMassEditActionOperatorForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            $this->batchOperator->initializeOperation($productIds);
            $form = $this->getMassEditActionOperatorForm();
        }

        return $this->render(
            sprintf('PimCatalogBundle:MassEditAction:configure/%s.html.twig', $operationAlias),
            array(
                'form'          => $form->createView(),
                'batchOperator' => $this->batchOperator,
                'productIds'    => $productIds,
            )
        );
    }

    /**
     * @param Request $request
     * @param string  $operationAlias
     *
     * @AclAncestor("pim_catalog_mass_edit_perform")
     * @throws NotFoundHttpException
     * @return template|RedirectResponse
     */
    public function performAction(Request $request, $operationAlias)
    {
        try {
            $this->batchOperator->setOperationAlias($operationAlias);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage(), $e);
        }

        $productIds = $this->getProductIds($request);
        if (!$productIds) {
            return $this->redirectToRoute('pim_catalog_product_index');
        }

        // Hacky hack for the edit common attribute operation to work
        // first time is to set diplayed attributes and locale
        $this->batchOperator->initializeOperation($productIds);
        $form = $this->getMassEditActionOperatorForm();
        $form->bind($request);

        //second time is to set values
        $this->batchOperator->initializeOperation($productIds);
        $form = $this->getMassEditActionOperatorForm();
        $form->bind($request);

        if ($form->isValid()) {
            $this->batchOperator->performOperation($productIds);
            $this->addFlash('success', sprintf('pim_catalog.mass_edit_action.%s.success_flash', $operationAlias));

            return $this->redirectToRoute('pim_catalog_product_index');
        }

        return $this->render(
            sprintf('PimCatalogBundle:MassEditAction:configure/%s.html.twig', $operationAlias),
            array(
                'form'          => $form->createView(),
                'batchOperator' => $this->batchOperator,
                'productIds'    => $productIds,
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \LogicException
     */
    public function deleteAction(Request $request)
    {
        $productIds = $this->getProductIds($request);
        $this->productManager->removeAll($productIds);
        $entitiesCount = count($productIds);
        $options = array('count' => $entitiesCount);

        $response = new MassActionResponse(
            true,
            $this->getTranslator()->transChoice(
                'oro.grid.mass_action.delete.success_message',
                $entitiesCount,
                array('%count%' => $entitiesCount)
            ),
            $options
        );

        $data = array(
            'successful' => $response->isSuccessful(),
            'message'    => $response->getMessage(),
        );

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * @return Form
     */
    private function getMassEditActionOperatorForm()
    {
        return $this->createForm(
            new MassEditActionOperatorType(),
            $this->batchOperator,
            array('operations' => $this->batchOperator->getOperationChoices())
        );
    }

    /**
     * Get the product ids stored in the query string
     *
     * @param Request $request
     *
     * @return array
     */
    private function getProductIds(Request $request)
    {
        $inset = $request->query->get('inset');
        if ($inset === '0') {
            /** @var $gridManager ProductDatagridManager */
            $gridManager = $this->datagridHelper->getDatagridManager('product');
            $gridManager->setFilterTreeId($request->get('treeId', 0));
            $gridManager->setFilterCategoryId($request->get('categoryId', 0));
            $parameters = $this->parametersParser->parse($request);
            $filters = $parameters['filters'];
            $datagrid = $gridManager->getDatagrid();
            $datagrid->getParameters()->set(ParametersInterface::FILTER_PARAMETERS, $filters);
            $ids = $datagrid->getAllIds();

            return $ids;

        } elseif ($values = $request->query->get('values')) {
            return explode(',', $values);

        } else {
            return $request->query->get('products');
        }
    }
}
