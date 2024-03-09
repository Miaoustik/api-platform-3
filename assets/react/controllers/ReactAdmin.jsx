import {HydraAdmin, ResourceGuesser} from "@api-platform/admin";
import React from 'react';

export default (props) => (
    <HydraAdmin entrypoint={props.entryPoint} >
        <ResourceGuesser name={'users'} />
        <ResourceGuesser name={'treasures'} />
    </HydraAdmin>
)
