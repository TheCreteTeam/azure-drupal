package com.esap_be.demo.Models.Entities;

import com.fasterxml.jackson.annotation.JsonProperty;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

@NoArgsConstructor
@AllArgsConstructor
@Getter
@Setter
public class ISOCodeDetails {
    @JsonProperty("Code/Subdivision Change")
    private String codeSubdivisionChange;

    @JsonProperty("Date Issued")
    private String dateIssued;

    @JsonProperty("Description of Change")
    private String descriptionOfChange;

    @JsonProperty("Edition/Newsletter")
    private String editionNewsletter;
}