<?php

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Lend Engine API",
 *         @OA\License(name="MIT")
 *     ),
 *     @OA\Server(
 *         description="API server",
 *         url="petstore.swagger.io",
 *     ),
 * )
 */

/**
 *  @OA\Schema(
 *      schema="Error",
 *      required={"code", "message"},
 *      @OA\Property(
 *          property="code",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @OA\Property(
 *          property="message",
 *          type="string"
 *      )
 *  )
 */